<?php

namespace App\Console\Commands;

use App\Models\RecurringProfile;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateRecurringTransactions extends Command
{
    protected $signature = 'app:generate-recurring-transactions';

    protected $description = 'Gera as transações a partir de perfis recorrentes ativos';

    public function handle()
    {
        $today = Carbon::today();
        $processedCount = 0;

        // Fetch all active profiles that have an occurrence date <= today
        // We do not lock all of them at once to avoid deadlocks. We just get their IDs.
        $profilesToProcess = RecurringProfile::where('status', 'active')
            ->where('next_occurrence_date', '<=', $today->toDateString())
            ->pluck('id');

        foreach ($profilesToProcess as $profileId) {
            $processedCount += $this->processProfile($profileId, $today);
        }

        $this->info("Geração concluída. {$processedCount} transações criadas.");
    }

    private function processProfile(string $profileId, Carbon $today): int
    {
        $createdTransactions = 0;

        while (true) {
            $transactionCreated = false;

            try {
                $transactionCreated = DB::transaction(function () use ($profileId, $today) {
                    $profile = RecurringProfile::lockForUpdate()->find($profileId);

                    // Re-verifica condições sob lock
                    if (! $profile || $profile->status !== 'active' || $profile->next_occurrence_date > $today) {
                        return false;
                    }

                    if ($profile->end_date && $profile->next_occurrence_date > $profile->end_date) {
                        $profile->update(['status' => 'completed']);

                        return false;
                    }

                    $occurrenceDate = Carbon::parse($profile->next_occurrence_date);

                    // Cria a transação
                    $transaction = new Transaction;
                    $transaction->user_id = $profile->user_id;
                    $transaction->account_id = $profile->account_id;
                    $transaction->category_id = $profile->category_id;
                    $transaction->type = $profile->type;
                    $transaction->amount = $profile->amount;
                    $transaction->currency = $profile->currency;
                    $transaction->transaction_date = $occurrenceDate->toDateString();
                    $transaction->posted_at = now();
                    $transaction->status = 'posted';
                    $transaction->description = "{$profile->name}";
                    $transaction->recurring_profile_id = $profile->id;
                    $transaction->recurring_occurrence_date = $occurrenceDate->toDateString();

                    try {
                        // Usamos uma transação aninhada (SAVEPOINT) para a inserção.
                        // Assim, se o Postgres/SQLite estourar constraint de Unique,
                        // apenas o Savepoint faz rollback, mantendo a transação externa saudável
                        // para podermos atualizar a next_occurrence_date.
                        DB::transaction(function () use ($transaction) {
                            $transaction->save();
                        });
                    } catch (QueryException $e) {
                        $errorCode = $e->getCode();

                        // Verifica se é violação de Unique Constraint da ocorrência
                        // 23505 = Postgres Unique Violation
                        // 19 / 2067 / 23000 = SQLite/MySQL Integrity Constraint
                        if ($errorCode == '23505' || $errorCode == '23000' || str_contains($e->getMessage(), 'UNIQUE constraint failed') || str_contains($e->getMessage(), 'recurring_signature_unique')) {
                            $exists = Transaction::where('recurring_profile_id', $profile->id)
                                ->where('recurring_occurrence_date', $occurrenceDate->toDateString())
                                ->exists();

                            if ($exists) {
                                // Foi duplicado (gerado por outro worker). Apenas ignoramos o insert
                                // e seguimos para avançar a data.
                            } else {
                                // Unique constraint lançada mas não encontramos o registro? Estranho, lançar erro original
                                throw $e;
                            }
                        } else {
                            throw $e;
                        }
                    }

                    // Avança a data usando a frequência
                    $nextDate = $this->calculateNextOccurrence($profile);

                    $profile->update(['next_occurrence_date' => $nextDate]);

                    return true;
                });

                if (! $transactionCreated) {
                    break;
                }

                $createdTransactions++;

            } catch (\Exception $e) {
                // Captura erro para não parar a execução inteira do command, mas loga.
                Log::error("Erro processando RecurringProfile {$profileId}: ".$e->getMessage());
                break; // Se deu throw e causou rollback, paramos o processamento deste perfil nesta execução
            }
        }

        return $createdTransactions;
    }

    private function calculateNextOccurrence(RecurringProfile $profile): Carbon
    {
        $date = $profile->next_occurrence_date->copy();

        switch ($profile->frequency) {
            case 'daily':
                $date->addDay();
                break;
            case 'weekly':
                $date->addWeek();
                break;
            case 'biweekly':
                $date->addWeeks(2);
                break;
            case 'monthly':
                $date = $this->addMonthsMaintainingStartDay($date, $profile, 1);
                break;
            case 'quarterly':
                $date = $this->addMonthsMaintainingStartDay($date, $profile, 3);
                break;
            case 'semiannually':
                $date = $this->addMonthsMaintainingStartDay($date, $profile, 6);
                break;
            case 'annually':
                $date = $this->addMonthsMaintainingStartDay($date, $profile, 12);
                break;
        }

        return $date;
    }

    private function addMonthsMaintainingStartDay(Carbon $currentDate, RecurringProfile $profile, int $monthsToAdd): Carbon
    {
        $metadata = $profile->metadata ?? [];
        $startDay = $metadata['start_day'] ?? $currentDate->day;

        $nextDate = $currentDate->copy()->addMonthsNoOverflow($monthsToAdd);

        // Ajusta forçando o dia original (start_day), mas garante que não ultrapassa
        // o limite do novo mês (ex: tenta forçar dia 31 em Fev, clamp para 28/29).
        $daysInNextMonth = $nextDate->daysInMonth;
        $targetDay = min($startDay, $daysInNextMonth);

        $nextDate->day($targetDay);

        return $nextDate;
    }
}
