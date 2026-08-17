<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Budget;
use App\Models\CreditCardInvoice;
use App\Models\CreditCardTransaction;
use App\Models\FinancialGoal;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Get the total balance of all accounts for the user.
     */
    public function getAccountsBalance(string $userId): string
    {
        $activeAccountIds = Account::where('user_id', $userId)
            ->where('status', 'active')
            ->pluck('id');

        if ($activeAccountIds->isEmpty()) {
            return '0.00';
        }

        $sumAmounts = function ($query) {
            $total = '0.00';
            foreach ($query->toBase()->pluck('amount') as $amount) {
                $amtStr = is_string($amount) ? $amount : number_format($amount, 2, '.', '');
                $total = bcadd($total, $amtStr, 2);
            }
            return $total;
        };

        $incomes = $sumAmounts(Transaction::where('user_id', $userId)->whereIn('account_id', $activeAccountIds)->where('type', 'income'));
        $expenses = $sumAmounts(Transaction::where('user_id', $userId)->whereIn('account_id', $activeAccountIds)->whereIn('type', ['expense', 'payment']));
        $outgoingTransfers = $sumAmounts(\App\Models\Transfer::where('user_id', $userId)->whereIn('from_account_id', $activeAccountIds));
        $incomingTransfers = $sumAmounts(\App\Models\Transfer::where('user_id', $userId)->whereIn('to_account_id', $activeAccountIds));
        $goalContributions = $sumAmounts(\App\Models\GoalContribution::where('user_id', $userId)->whereIn('account_id', $activeAccountIds));

        $totalIn = bcadd($incomes, $incomingTransfers, 2);
        $totalOut = bcadd($expenses, bcadd($outgoingTransfers, $goalContributions, 2), 2);

        return bcsub($totalIn, $totalOut, 2);
    }

    /**
     * Get the sum of all income transactions for the specified month.
     */
    public function getMonthlyIncomes(string $userId, Carbon $month): string
    {
        $sum = Transaction::where('user_id', $userId)
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->sum('amount');

        return number_format($sum, 2, '.', '');
    }

    /**
     * Get the sum of all expense transactions for the specified month.
     * Excludes 'payment' and 'transfer'.
     */
    public function getMonthlyExpenses(string $userId, Carbon $month): string
    {
        $sum = Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->sum('amount');

        return number_format($sum, 2, '.', '');
    }

    /**
     * Get the total pending debt across all open/closed credit card invoices.
     * Calculated as total_amount - paid_amount.
     */
    public function getPendingInvoicesTotal(string $userId): string
    {
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // Fallback for sqlite due to limited support for raw sum expressions sometimes
            $invoices = CreditCardInvoice::where('user_id', $userId)
                ->whereIn('status', ['open', 'closed'])
                ->get(['total_amount', 'paid_amount']);

            $totalPending = '0.00';
            foreach ($invoices as $invoice) {
                $pending = bcsub((string)$invoice->total_amount, (string)$invoice->paid_amount, 2);
                if ($pending > 0) {
                    $totalPending = bcadd($totalPending, $pending, 2);
                }
            }

            return $totalPending;
        }

        // Optimized sum for Postgres/MySQL
        $totalPending = CreditCardInvoice::where('user_id', $userId)
            ->whereIn('status', ['open', 'closed'])
            ->whereRaw('total_amount > paid_amount')
            ->sum(\Illuminate\Support\Facades\DB::raw('total_amount - paid_amount'));

        return number_format((float) $totalPending, 2, '.', '');
    }

    /**
     * Get active budgets for the given month, along with their usage progress.
     */
    public function getActiveBudgets(string $userId, Carbon $month, BudgetService $budgetService): Collection
    {
        $budgets = Budget::where('user_id', $userId)
            ->where('status', 'active')
            ->where(function ($q) use ($month) {
                $q->where(function ($q2) use ($month) {
                    $q2->whereNull('period_start')
                        ->orWhere('period_start', '<=', $month->copy()->endOfMonth());
                })->where(function ($q2) use ($month) {
                    $q2->whereNull('period_end')
                        ->orWhere('period_end', '>=', $month->copy()->startOfMonth());
                });
            })
            ->get();

        $result = collect();
        foreach ($budgets as $budget) {
            $progress = $budgetService->getBudgetProgress($budget);
            $result->push([
                'id' => $budget->id,
                'name' => $budget->name,
                'target_amount' => $progress['target_amount'],
                'actual_amount' => $progress['actual_amount_sum'],
                'percentage_used' => $progress['percentage_used'],
            ]);
        }

        return $result;
    }

    /**
     * Get active financial goals.
     */
    public function getActiveGoals(string $userId): Collection
    {
        $goals = FinancialGoal::where('user_id', $userId)
            ->where('status', 'active')
            ->get();

        $result = collect();
        foreach ($goals as $goal) {
            $percentage = 0;
            if ($goal->target_amount > 0) {
                $percentage = min(100, round(($goal->current_amount / $goal->target_amount) * 100));
            }
            $result->push([
                'id' => $goal->id,
                'name' => $goal->name,
                'current_amount' => $goal->current_amount,
                'target_amount' => $goal->target_amount,
                'percentage' => $percentage,
            ]);
        }

        return $result;
    }

    /**
     * Get cash flow evolution for the last N months.
     * Excludes transfers and payments.
     */
    public function getCashFlowEvolution(string $userId, int $months = 6): array
    {
        $result = [
            'labels' => [],
            'incomes' => [],
            'expenses' => [],
        ];

        $startMonth = Carbon::today()->startOfMonth()->subMonths($months - 1);
        $endMonth = Carbon::today()->endOfMonth();

        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $isSqlite = $driver === 'sqlite';

        $sums = [];

        if ($isSqlite) {
            // SQLite doesn't easily support YEAR/MONTH grouping in the same way as Postgres/MySQL natively without strftime
            $transactions = Transaction::where('user_id', $userId)
                ->whereIn('type', ['income', 'expense'])
                ->where('transaction_date', '>=', $startMonth->toDateString())
                ->where('transaction_date', '<=', $endMonth->toDateString())
                ->get(['transaction_date', 'amount', 'type']);

            foreach ($transactions as $tx) {
                $txDate = Carbon::parse($tx->transaction_date);
                $key = $txDate->format('Y-m');
                if (!isset($sums[$key])) {
                    $sums[$key] = ['income' => '0.00', 'expense' => '0.00'];
                }
                if ($tx->type === 'income') {
                    $sums[$key]['income'] = bcadd($sums[$key]['income'], number_format((float) $tx->amount, 2, '.', ''), 2);
                } else {
                    $sums[$key]['expense'] = bcadd($sums[$key]['expense'], number_format((float) $tx->amount, 2, '.', ''), 2);
                }
            }
        } else {
            // Postgres/MySQL optimized
            $queryResult = Transaction::where('user_id', $userId)
                ->whereIn('type', ['income', 'expense'])
                ->where('transaction_date', '>=', $startMonth->toDateString())
                ->where('transaction_date', '<=', $endMonth->toDateString())
                ->selectRaw("
                    type,
                    SUM(amount) as total,
                    ".($driver === 'pgsql' ? "to_char(transaction_date, 'YYYY-MM')" : "DATE_FORMAT(transaction_date, '%Y-%m')")." as month_key
                ")
                ->groupBy('type', 'month_key')
                ->get();

            foreach ($queryResult as $row) {
                $key = $row->month_key;
                if (!isset($sums[$key])) {
                    $sums[$key] = ['income' => '0.00', 'expense' => '0.00'];
                }
                if ($row->type === 'income') {
                    $sums[$key]['income'] = number_format((float) $row->total, 2, '.', '');
                } else {
                    $sums[$key]['expense'] = number_format((float) $row->total, 2, '.', '');
                }
            }
        }

        for ($i = $months - 1; $i >= 0; $i--) {
            $m = Carbon::today()->startOfMonth()->subMonths($i);
            $key = $m->format('Y-m');
            
            $result['labels'][] = ucfirst($m->translatedFormat('M y'));
            $result['incomes'][] = isset($sums[$key]) ? (float) $sums[$key]['income'] : 0.0;
            $result['expenses'][] = isset($sums[$key]) ? (float) $sums[$key]['expense'] : 0.0;
        }

        return $result;
    }

    /**
     * Get expenses by category for a specific month.
     * Combines Cash Expenses and Credit Card Transactions.
     * Excludes Invoice Payments (type=payment) to avoid double counting.
     */
    public function getExpensesByCategory(string $userId, Carbon $month): array
    {
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $isSqlite = $driver === 'sqlite';

        $startDate = $month->copy()->startOfMonth()->toDateString();
        $endDate = $month->copy()->endOfMonth()->toDateString();

        $categories = [];

        if ($isSqlite) {
            $txs = Transaction::with('category:id,name')
                ->where('user_id', $userId)
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->get(['category_id', 'amount']);

            $ccTxs = CreditCardTransaction::with('category:id,name')
                ->where('user_id', $userId)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->get(['category_id', 'amount']);

            foreach ($txs as $tx) {
                $catName = $tx->category ? $tx->category->name : 'Outros';
                if (!isset($categories[$catName])) {
                    $categories[$catName] = '0.00';
                }
                $categories[$catName] = bcadd($categories[$catName], number_format((float) $tx->amount, 2, '.', ''), 2);
            }

            foreach ($ccTxs as $tx) {
                $catName = $tx->category ? $tx->category->name : 'Outros';
                if (!isset($categories[$catName])) {
                    $categories[$catName] = '0.00';
                }
                $categories[$catName] = bcadd($categories[$catName], number_format((float) $tx->amount, 2, '.', ''), 2);
            }
        } else {
            // Postgres/MySQL Optimized
            $txSums = Transaction::with('category:id,name')
                ->where('user_id', $userId)
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->select('category_id', \Illuminate\Support\Facades\DB::raw('SUM(amount) as total'))
                ->groupBy('category_id')
                ->get();

            $ccSums = CreditCardTransaction::with('category:id,name')
                ->where('user_id', $userId)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->select('category_id', \Illuminate\Support\Facades\DB::raw('SUM(amount) as total'))
                ->groupBy('category_id')
                ->get();

            foreach ($txSums as $tx) {
                $catName = $tx->category ? $tx->category->name : 'Outros';
                if (!isset($categories[$catName])) {
                    $categories[$catName] = '0.00';
                }
                $categories[$catName] = bcadd($categories[$catName], number_format((float) $tx->total, 2, '.', ''), 2);
            }

            foreach ($ccSums as $tx) {
                $catName = $tx->category ? $tx->category->name : 'Outros';
                if (!isset($categories[$catName])) {
                    $categories[$catName] = '0.00';
                }
                $categories[$catName] = bcadd($categories[$catName], number_format((float) $tx->total, 2, '.', ''), 2);
            }
        }

        $result = [
            'labels' => [],
            'series' => [],
        ];

        // filter out zero amount categories and sort descending
        $filtered = collect($categories)->map(function($amount, $name) {
            return ['name' => $name, 'amount' => $amount];
        })->filter(function ($cat) {
            return (float) $cat['amount'] > 0;
        })->sortByDesc('amount');

        foreach ($filtered as $cat) {
            $result['labels'][] = $cat['name'];
            $result['series'][] = (float) $cat['amount'];
        }

        return $result;
    }
}
