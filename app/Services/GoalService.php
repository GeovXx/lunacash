<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FinancialGoal;
use App\Models\GoalContribution;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GoalService
{
    /**
     * Create a new Financial Goal
     */
    public function createGoal(array $data, string $userId): FinancialGoal
    {
        $goal = new FinancialGoal($data);
        $goal->user_id = $userId;
        $goal->current_amount = 0;
        $goal->status = 'active';

        $goal->save();

        return $goal;
    }

    /**
     * Update an existing Financial Goal
     */
    public function updateGoal(string $goalId, array $data, string $userId): FinancialGoal
    {
        $goal = FinancialGoal::query()->where('user_id', $userId)->findOrFail($goalId);

        // Prevent editing target_amount below current_amount
        if (isset($data['target_amount']) && bccomp($data['target_amount'], $goal->current_amount, 2) === -1) {
            throw new InvalidArgumentException('Target amount cannot be less than current amount.');
        }

        $goal->update($data);

        return $goal;
    }

    /**
     * Make a contribution to a goal
     */
    public function addContribution(string $goalId, string $accountId, string $amount, string $date, string $userId, ?string $description = null): GoalContribution
    {
        if (bccomp($amount, '0.00', 2) !== 1) {
            throw new InvalidArgumentException('Contribution amount must be greater than zero.');
        }

        return DB::transaction(function () use ($goalId, $accountId, $amount, $date, $userId, $description) {
            // 1. Lock Account
            $account = Account::query()
                ->where('user_id', $userId)
                ->where('id', $accountId)
                ->lockForUpdate()
                ->firstOrFail();

            // 2. Lock FinancialGoal
            $goal = FinancialGoal::query()
                ->where('user_id', $userId)
                ->where('id', $goalId)
                ->lockForUpdate()
                ->firstOrFail();

            // 3. Validate Goal Status
            if ($goal->status !== 'active') {
                throw new InvalidArgumentException('Cannot contribute to a goal that is not active.');
            }

            // 4. Validate Available Balance
            if (bccomp($amount, $account->balance, 2) === 1) {
                throw new InvalidArgumentException('Insufficient account balance for this contribution.');
            }

            // 5. Validate Target Remaining
            $newAmount = bcadd($goal->current_amount, $amount, 2);
            $compareTarget = bccomp($newAmount, $goal->target_amount, 2);

            if ($compareTarget === 1) {
                throw new InvalidArgumentException('Contribution exceeds the goal target amount.');
            }

            // 6. Create Goal Contribution
            $contribution = new GoalContribution([
                'financial_goal_id' => $goal->id,
                'account_id' => $account->id,
                'amount' => $amount,
                'currency' => $goal->currency,
                'contribution_date' => $date,
                'description' => $description,
            ]);
            $contribution->user_id = $userId;

            $contribution->save();

            // 7. Update current_amount and status
            $goal->current_amount = $newAmount;

            if ($compareTarget === 0) {
                $goal->status = 'completed';
            }

            $goal->save();

            return $contribution;
        });
    }
}
