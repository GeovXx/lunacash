<?php

namespace App\Services;

use App\Models\CreditCardInvoice;
use App\Models\FinancialGoal;
use App\Models\FinancialObligation;
use App\Models\RecurringProfile;
use App\Models\Transaction;
use Carbon\Carbon;

class CalendarService
{
    /**
     * Retorna os eventos temporais de um usuário dentro de um determinado período.
     */
    public function getEventsForPeriod(string $userId, string $startDate, string $endDate): array
    {
        $events = [];

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // 1. Transactions
        $transactions = Transaction::with(['category', 'account'])
            ->where('user_id', $userId)
            ->whereBetween('transaction_date', [$start, $end])
            ->get();

        foreach ($transactions as $transaction) {
            $events[] = [
                'id' => 'tx_'.$transaction->id,
                'source_type' => 'transaction',
                'title' => $transaction->description,
                'amount' => $transaction->amount,
                'direction' => $transaction->type, // income, expense, transfer
                'date' => $transaction->transaction_date->toDateString(),
                'status' => $transaction->status,
                'category_name' => $transaction->category ? $transaction->category->name : null,
                'account_name' => $transaction->account ? $transaction->account->name : null,
            ];
        }

        // 2. FinancialObligations (only open or overdue)
        $obligations = FinancialObligation::with(['category', 'account'])
            ->where('user_id', $userId)
            ->whereIn('status', ['open', 'overdue'])
            ->whereBetween('due_date', [$start, $end])
            ->get();

        foreach ($obligations as $obligation) {
            $events[] = [
                'id' => 'ob_'.$obligation->id,
                'source_type' => 'obligation',
                'title' => $obligation->title,
                'amount' => $obligation->amount,
                'direction' => $obligation->direction, // payable, receivable
                'date' => $obligation->due_date->toDateString(),
                'status' => $obligation->status,
                'category_name' => $obligation->category ? $obligation->category->name : null,
                'account_name' => $obligation->account ? $obligation->account->name : null,
            ];
        }

        // 3. CreditCardInvoices (open, closed)
        $invoices = CreditCardInvoice::with('creditCard')
            ->where('user_id', $userId)
            ->whereIn('status', ['open', 'closed'])
            ->whereBetween('due_date', [$start, $end])
            ->get();

        foreach ($invoices as $invoice) {
            $remainingAmount = bcsub((string) $invoice->total_amount, (string) ($invoice->paid_amount ?? '0.00'), 2);

            if ($remainingAmount <= 0) {
                continue; // Parcialmente ou totalmente paga logicamente tratada se o saldo for 0
            }

            $events[] = [
                'id' => 'inv_'.$invoice->id,
                'source_type' => 'invoice',
                'title' => 'Fatura '.$invoice->creditCard->name,
                'amount' => $remainingAmount,
                'direction' => 'expense', // Fatura é sempre saída
                'date' => $invoice->due_date->toDateString(),
                'status' => $invoice->status,
                'category_name' => null,
                'account_name' => $invoice->creditCard->name,
            ];
        }

        // 4. FinancialGoals
        $goals = FinancialGoal::query()
            ->where('user_id', $userId)
            ->whereBetween('target_date', [$start, $end])
            ->get();

        foreach ($goals as $goal) {
            $events[] = [
                'id' => 'goal_'.$goal->id,
                'source_type' => 'goal',
                'title' => $goal->name,
                'amount' => $goal->target_amount,
                'direction' => 'neutral',
                'date' => $goal->target_date->toDateString(),
                'status' => $goal->status,
                'category_name' => null,
                'account_name' => null,
            ];
        }

        // 5. Recurring Profiles (Projection)
        $profiles = RecurringProfile::with(['category', 'account'])
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->get();

        // Extraimos transactions dates geradas para evitar projetar duplicatas no calendário
        // Limitamos para as geradas pelo recorrente e que estejam no periodo para otimização
        $existingRecurringTxDates = Transaction::query()
            ->where('user_id', $userId)
            ->whereNotNull('recurring_profile_id')
            ->whereBetween('recurring_occurrence_date', [$start, $end])
            ->select('recurring_profile_id', 'recurring_occurrence_date')
            ->get()
            ->groupBy('recurring_profile_id')
            ->map(function ($items) {
                return $items->pluck('recurring_occurrence_date')->toArray();
            });

        foreach ($profiles as $profile) {
            $currentDate = $profile->next_occurrence_date->copy();

            // Avança até chegar no start date, ou parar se passar o end date
            while ($currentDate <= $end) {
                if ($profile->end_date && $currentDate > $profile->end_date) {
                    break;
                }

                if ($currentDate >= $start) {
                    $dateString = $currentDate->toDateString();

                    // Verifica se já existe transação
                    $hasTransaction = false;
                    if (isset($existingRecurringTxDates[$profile->id]) && in_array($dateString, $existingRecurringTxDates[$profile->id])) {
                        $hasTransaction = true;
                    }

                    if (! $hasTransaction) {
                        $events[] = [
                            'id' => 'rec_'.$profile->id.'_'.$dateString,
                            'source_type' => 'recurring',
                            'title' => $profile->name,
                            'amount' => $profile->amount,
                            'direction' => $profile->type,
                            'date' => $dateString,
                            'status' => 'projected',
                            'category_name' => $profile->category ? $profile->category->name : null,
                            'account_name' => $profile->account ? $profile->account->name : null,
                        ];
                    }
                }

                $currentDate = $this->calculateNextOccurrence($currentDate, $profile);
            }
        }

        // Sort events by date ascending
        usort($events, function ($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        return $events;
    }

    private function calculateNextOccurrence(Carbon $currentDate, RecurringProfile $profile): Carbon
    {
        $date = $currentDate->copy();

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
        $startDay = $metadata['start_day'] ?? $profile->next_occurrence_date->day;

        $nextDate = $currentDate->copy()->addMonthsNoOverflow($monthsToAdd);

        $daysInNextMonth = $nextDate->daysInMonth;
        $targetDay = min($startDay, $daysInNextMonth);

        $nextDate->day($targetDay);

        return $nextDate;
    }
}
