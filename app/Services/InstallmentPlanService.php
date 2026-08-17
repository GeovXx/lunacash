<?php

namespace App\Services;

use App\Models\Category;
use App\Models\InstallmentPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class InstallmentPlanService
{
    public function createPlanWithObligations(array $data)
    {
        return DB::transaction(function () use ($data) {
            $userId = auth()->id() ?? $data['user_id'] ?? throw new \InvalidArgumentException('User ID is required.');

            // 1. Validar propriedade da conta
            $account = \App\Models\Account::where('id', $data['account_id'])
                ->where('user_id', $userId)
                ->firstOrFail();

            // 2. Validar direção e propriedade da categoria
            $category = Category::where('id', $data['category_id'])
                ->where(function ($q) use ($userId) {
                    $q->where('user_id', $userId)->orWhereNull('user_id');
                })->firstOrFail();

            if ($data['direction'] === 'payable' && $category->type !== 'expense') {
                throw new InvalidArgumentException('Payable plans must use an expense category.');
            }

            if ($data['direction'] === 'receivable' && $category->type !== 'income') {
                throw new InvalidArgumentException('Receivable plans must use an income category.');
            }

            // 3. Criar o plano usando $userId resolvido
            $plan = new InstallmentPlan([
                'account_id' => $data['account_id'],
                'category_id' => $data['category_id'],
                'direction' => $data['direction'],
                'title' => $data['title'],
                'total_amount' => $data['total_amount'],
                'installments_count' => $data['installments_count'],
                'first_due_date' => $data['first_due_date'],
                'frequency' => $data['frequency'],
                'notes' => $data['notes'] ?? null,
                'status' => 'active', // Inicial
            ]);
            $plan->user_id = $userId;
            $plan->save();

            // 3. Matemática Financeira sem Float (divisão em centavos)
            $totalCents = (int) round($plan->total_amount * 100);
            $count = $plan->installments_count;

            $baseInstallmentCents = intdiv($totalCents, $count);
            $remainderCents = $totalCents % $count;

            $obligations = [];
            $baseDate = Carbon::parse($plan->first_due_date);

            // 4. Gerar parcelas
            for ($i = 1; $i <= $count; $i++) {
                $installmentCents = $baseInstallmentCents;

                // O primeiro mês absorve o resto (para garantir que a soma seja exata)
                if ($i === 1) {
                    $installmentCents += $remainderCents;
                }

                $amount = $installmentCents / 100;

                // 5. Calcular data determinística
                $dueDate = clone $baseDate;
                if ($i > 1) {
                    $modifier = $i - 1;
                    if ($plan->frequency === 'monthly') {
                        $dueDate->addMonthsNoOverflow($modifier);
                    } elseif ($plan->frequency === 'weekly') {
                        $dueDate->addWeeks($modifier);
                    } elseif ($plan->frequency === 'biweekly') {
                        $dueDate->addWeeks($modifier * 2);
                    }
                }

                $obligations[] = [
                    'id' => Str::uuid(),
                    'user_id' => $plan->user_id,
                    'account_id' => $plan->account_id,
                    'category_id' => $plan->category_id,
                    'installment_plan_id' => $plan->id,
                    'installment_number' => $i,
                    'direction' => $plan->direction,
                    'title' => $plan->title.' ('.$i.'/'.$count.')',
                    'amount' => $amount,
                    'currency' => 'BRL',
                    'due_date' => $dueDate->toDateString(),
                    'issued_date' => today()->toDateString(),
                    'status' => 'open',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Inserir todas de uma vez
            $plan->obligations()->insert($obligations);

            // 6. Atualizar status para completed
            $plan->update(['status' => 'completed']);

            return $plan;
        });
    }

    public function cancelPlan(InstallmentPlan $plan)
    {
        if (auth()->check() && $plan->user_id !== auth()->id()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Unauthorized.');
        }

        DB::transaction(function () use ($plan) {
            $plan->update(['status' => 'cancelled']);
        });

        return $plan;
    }
}
