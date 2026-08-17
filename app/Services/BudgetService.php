<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\Category;
use App\Models\CreditCardTransaction;
use App\Models\Transaction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BudgetService
{
    public function createBudget(array $data): Budget
    {
        return DB::transaction(function () use ($data) {
            $budget = new Budget([
                'name' => $data['name'],
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'target_amount' => $data['target_amount'] ?? 0,
                'status' => 'active',
            ]);
            $budget->user_id = $data['user_id'];
            $budget->save();
            return $budget;
        });
    }

    public function updateBudget(Budget $budget, array $data): Budget
    {
        $this->ensureOwns($budget, $data['user_id']);

        return DB::transaction(function () use ($budget, $data) {
            $budget->update([
                'name' => $data['name'] ?? $budget->name,
                'period_start' => $data['period_start'] ?? $budget->period_start,
                'period_end' => $data['period_end'] ?? $budget->period_end,
                'target_amount' => $data['target_amount'] ?? $budget->target_amount,
            ]);

            return $budget;
        });
    }

    public function addBudgetLine(Budget $budget, array $data): BudgetLine
    {
        $this->ensureOwns($budget, $data['user_id']);
        $this->validateCategory($data['category_id'], $data['user_id']);

        return DB::transaction(function () use ($budget, $data) {
            $line = new BudgetLine([
                'budget_id' => $budget->id,
                'category_id' => $data['category_id'],
                'name' => $data['name'] ?? 'Line',
                'planned_amount' => $data['planned_amount'],
                'status' => 'active',
            ]);
            $line->user_id = $data['user_id'];
            $line->save();
            return $line;
        });
    }

    public function updateBudgetLine(BudgetLine $line, array $data): BudgetLine
    {
        $this->ensureOwns($line, $data['user_id']);
        if (isset($data['category_id']) && $data['category_id'] !== $line->category_id) {
            $this->validateCategory($data['category_id'], $data['user_id']);
        }

        return DB::transaction(function () use ($line, $data) {
            $line->update([
                'category_id' => $data['category_id'] ?? $line->category_id,
                'planned_amount' => $data['planned_amount'] ?? $line->planned_amount,
            ]);

            return $line;
        });
    }

    public function removeBudgetLine(BudgetLine $line, $userId): void
    {
        $this->ensureOwns($line, $userId);
        $line->delete();
    }

    public function getBudgetProgress(Budget $budget): array
    {
        $driver = DB::connection()->getDriverName();
        $isSqlite = $driver === 'sqlite';

        $lines = $budget->budgetLines()->with('category')->get();
        $lineCategoryIds = $lines->pluck('category_id')->toArray();

        $result = [
            'target_amount' => number_format((float) $budget->target_amount, 2, '.', ''),
            'planned_amount_sum' => '0.00',
            'actual_amount_sum' => '0.00',
            'excess_amount' => '0.00',
            'percentage_used' => 0,
            'lines' => [],
        ];

        if (empty($lineCategoryIds)) {
            return $this->finalizeBudgetProgress($result);
        }

        $queryTx = Transaction::where('user_id', $budget->user_id)
            ->where('type', 'expense')
            ->whereIn('category_id', $lineCategoryIds)
            ->where('transaction_date', '>=', $budget->period_start)
            ->where('transaction_date', '<=', $budget->period_end);
        
        $queryCc = CreditCardTransaction::where('user_id', $budget->user_id)
            ->whereIn('category_id', $lineCategoryIds)
            ->where('transaction_date', '>=', $budget->period_start)
            ->where('transaction_date', '<=', $budget->period_end);

        $txSums = [];
        $ccSums = [];

        if ($isSqlite) {
            $txRows = $queryTx->select('category_id', 'amount')->toBase()->get();
            $ccRows = $queryCc->select('category_id', 'amount')->toBase()->get();
            
            foreach ($txRows as $row) {
                if (!isset($txSums[$row->category_id])) $txSums[$row->category_id] = '0.00';
                $txSums[$row->category_id] = bcadd($txSums[$row->category_id], number_format((float) $row->amount, 2, '.', ''), 2);
            }
            foreach ($ccRows as $row) {
                if (!isset($ccSums[$row->category_id])) $ccSums[$row->category_id] = '0.00';
                $ccSums[$row->category_id] = bcadd($ccSums[$row->category_id], number_format((float) $row->amount, 2, '.', ''), 2);
            }
        } else {
            $txSumsRaw = $queryTx->select('category_id', DB::raw('SUM(amount) as total'))->groupBy('category_id')->get();
            $ccSumsRaw = $queryCc->select('category_id', DB::raw('SUM(amount) as total'))->groupBy('category_id')->get();

            foreach ($txSumsRaw as $row) {
                $txSums[$row->category_id] = number_format((float) $row->total, 2, '.', '');
            }
            foreach ($ccSumsRaw as $row) {
                $ccSums[$row->category_id] = number_format((float) $row->total, 2, '.', '');
            }
        }

        foreach ($lines as $line) {
            $catId = $line->category_id;
            
            $txSum = $txSums[$catId] ?? '0.00';
            $ccSum = $ccSums[$catId] ?? '0.00';
            $actual = bcadd($txSum, $ccSum, 2);
            
            $planned = number_format((float) $line->planned_amount, 2, '.', '');
            
            $remaining = bcsub($planned, $actual, 2);
            if ($remaining < 0) {
                $remaining = '0.00';
            }
            
            $percentage = 0;
            if ($planned > 0) {
                $percentage = round(($actual / $planned) * 100);
            }

            $result['lines'][] = [
                'id' => $line->id,
                'category_id' => $line->category_id,
                'category_name' => $line->category->name ?? 'Unknown',
                'planned_amount' => $planned,
                'actual_amount' => $actual,
                'remaining_amount' => $remaining,
                'percentage_used' => $percentage,
            ];
            
            $result['planned_amount_sum'] = bcadd($result['planned_amount_sum'], $planned, 2);
            $result['actual_amount_sum'] = bcadd($result['actual_amount_sum'], $actual, 2);
        }

        return $this->finalizeBudgetProgress($result);
    }

    protected function finalizeBudgetProgress(array $result): array
    {
        $target = $result['target_amount'];
        $globalPlanned = $result['planned_amount_sum'];
        $globalActual = $result['actual_amount_sum'];
        
        $compareBase = $target > 0 ? $target : $globalPlanned;

        if ($compareBase > 0) {
            $result['percentage_used'] = round(($globalActual / $compareBase) * 100);
        }

        $excess = bcsub($globalActual, $compareBase, 2);
        if ($excess > 0) {
            $result['excess_amount'] = $excess;
        }

        return $result;
    }

    protected function ensureOwns($model, $userId): void
    {
        if ($model->user_id !== $userId) {
            throw new AuthorizationException('This action is unauthorized.');
        }
    }

    protected function validateCategory($categoryId, $userId): void
    {
        $category = Category::find($categoryId);
        if (! $category) {
            throw new ModelNotFoundException('Category not found.');
        }
        if ($category->user_id !== null && $category->user_id !== $userId) {
            throw new ModelNotFoundException('Category not found.');
        }
        if ($category->type !== 'expense') {
            throw new InvalidArgumentException('Budget lines must use an expense category.');
        }
    }
}
