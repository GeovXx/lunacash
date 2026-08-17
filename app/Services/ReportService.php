<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CreditCardInvoice;
use App\Models\CreditCardTransaction;
use App\Models\Transaction;
use Carbon\Carbon;

class ReportService
{
    /**
     * Get Cash Flow Report (Extrato Consolidado)
     * Visão: Caixa
     * Fonte: Transaction (income, expense, payment, transfer)
     * Sem CreditCardTransaction
     */
    public function getCashFlowReport(string $userId, Carbon $start, Carbon $end, int $perPage = 50): array
    {
        // Query base
        $query = Transaction::with(['account', 'category'])
            ->where('user_id', $userId)
            ->where('transaction_date', '>=', $start->toDateString())
            ->where('transaction_date', '<=', $end->toDateString().' 23:59:59')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        $paginator = $query->paginate($perPage);

        // Agregação de totais (Caixa)
        // Usar banco para calcular para evitar carregar milhares de transações na RAM
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            $allTransactions = Transaction::where('user_id', $userId)
                ->where('transaction_date', '>=', $start->toDateString())
                ->where('transaction_date', '<=', $end->toDateString().' 23:59:59')
                ->get(['type', 'amount']);

            $totalIncome = '0.00';
            $totalExpense = '0.00';
            $totalPayment = '0.00';

            foreach ($allTransactions as $tx) {
                if ($tx->type === 'income') {
                    $totalIncome = bcadd($totalIncome, number_format((float) $tx->amount, 2, '.', ''), 2);
                } elseif ($tx->type === 'expense') {
                    $totalExpense = bcadd($totalExpense, number_format((float) $tx->amount, 2, '.', ''), 2);
                } elseif ($tx->type === 'payment') {
                    $totalPayment = bcadd($totalPayment, number_format((float) $tx->amount, 2, '.', ''), 2);
                }
            }
        } else {
            $sums = Transaction::where('user_id', $userId)
                ->where('transaction_date', '>=', $start->toDateString())
                ->where('transaction_date', '<=', $end->toDateString().' 23:59:59')
                ->select('type', \Illuminate\Support\Facades\DB::raw('SUM(amount) as total'))
                ->groupBy('type')
                ->get()
                ->keyBy('type');

            $totalIncome = number_format((float) ($sums['income']->total ?? 0), 2, '.', '');
            $totalExpense = number_format((float) ($sums['expense']->total ?? 0), 2, '.', '');
            $totalPayment = number_format((float) ($sums['payment']->total ?? 0), 2, '.', '');
        }

        $totalOut = bcadd($totalExpense, $totalPayment, 2);
        $netFlow = bcsub($totalIncome, $totalOut, 2);

        return [
            'transactions' => $paginator,
            'summary' => [
                'income' => $totalIncome,
                'expense' => $totalExpense, // apenas despesas operacionais
                'payment' => $totalPayment, // apenas pagamentos de fatura
                'total_out' => $totalOut,
                'net_flow' => $netFlow,
            ],
        ];
    }

    /**
     * Get Expenses by Category (Visão de Consumo)
     * Visão: Competência
     * Fonte: Transaction(expense) + CreditCardTransaction
     * Exclui: payment, transfer, income
     */
    public function getCategoryReport(string $userId, Carbon $start, Carbon $end): array
    {
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        
        $categories = [];
        $totalConsumo = '0.00';

        if ($driver === 'sqlite') {
            $txs = Transaction::with('category:id,name')
                ->where('user_id', $userId)
                ->where('type', 'expense')
                ->where('transaction_date', '>=', $start->toDateString())
                ->where('transaction_date', '<=', $end->toDateString().' 23:59:59')
                ->get(['category_id', 'amount']);

            $ccTxs = CreditCardTransaction::with('category:id,name')
                ->where('user_id', $userId)
                ->where('transaction_date', '>=', $start->toDateString())
                ->where('transaction_date', '<=', $end->toDateString().' 23:59:59')
                ->get(['category_id', 'amount']);

            foreach ($txs as $tx) {
                $catName = $tx->category ? $tx->category->name : 'Outros';
                $amountStr = number_format((float) $tx->amount, 2, '.', '');
                if (!isset($categories[$catName])) {
                    $categories[$catName] = ['name' => $catName, 'amount' => '0.00'];
                }
                $categories[$catName]['amount'] = bcadd($categories[$catName]['amount'], $amountStr, 2);
                $totalConsumo = bcadd($totalConsumo, $amountStr, 2);
            }

            foreach ($ccTxs as $tx) {
                $catName = $tx->category ? $tx->category->name : 'Outros';
                $amountStr = number_format((float) $tx->amount, 2, '.', '');
                if (!isset($categories[$catName])) {
                    $categories[$catName] = ['name' => $catName, 'amount' => '0.00'];
                }
                $categories[$catName]['amount'] = bcadd($categories[$catName]['amount'], $amountStr, 2);
                $totalConsumo = bcadd($totalConsumo, $amountStr, 2);
            }
        } else {
            $txSums = Transaction::with('category:id,name')
                ->where('user_id', $userId)
                ->where('type', 'expense')
                ->where('transaction_date', '>=', $start->toDateString())
                ->where('transaction_date', '<=', $end->toDateString().' 23:59:59')
                ->select('category_id', \Illuminate\Support\Facades\DB::raw('SUM(amount) as total'))
                ->groupBy('category_id')
                ->get();

            $ccSums = CreditCardTransaction::with('category:id,name')
                ->where('user_id', $userId)
                ->where('transaction_date', '>=', $start->toDateString())
                ->where('transaction_date', '<=', $end->toDateString().' 23:59:59')
                ->select('category_id', \Illuminate\Support\Facades\DB::raw('SUM(amount) as total'))
                ->groupBy('category_id')
                ->get();

            foreach ($txSums as $tx) {
                $catName = $tx->category ? $tx->category->name : 'Outros';
                $amountStr = number_format((float) $tx->total, 2, '.', '');
                if (!isset($categories[$catName])) {
                    $categories[$catName] = ['name' => $catName, 'amount' => '0.00'];
                }
                $categories[$catName]['amount'] = bcadd($categories[$catName]['amount'], $amountStr, 2);
                $totalConsumo = bcadd($totalConsumo, $amountStr, 2);
            }

            foreach ($ccSums as $tx) {
                $catName = $tx->category ? $tx->category->name : 'Outros';
                $amountStr = number_format((float) $tx->total, 2, '.', '');
                if (!isset($categories[$catName])) {
                    $categories[$catName] = ['name' => $catName, 'amount' => '0.00'];
                }
                $categories[$catName]['amount'] = bcadd($categories[$catName]['amount'], $amountStr, 2);
                $totalConsumo = bcadd($totalConsumo, $amountStr, 2);
            }
        }

        // Ordena por maior valor
        $filtered = collect($categories)->filter(function ($cat) {
            return (float) $cat['amount'] > 0;
        })->sortByDesc('amount')->values()->all();

        return [
            'categories' => $filtered,
            'total_consumo' => $totalConsumo,
        ];
    }

    /**
     * Get Balances Report (Posição)
     * Visão: Patrimônio Simples (Contas vs Cartões)
     * Fonte: Account, CreditCardInvoice
     */
    public function getBalancesReport(string $userId): array
    {
        $accounts = Account::where('user_id', $userId)
            ->where('status', 'active')
            ->get();

        $invoices = CreditCardInvoice::with('creditCard')
            ->where('user_id', $userId)
            ->whereIn('status', ['open', 'closed'])
            ->get();

        $totalBalance = '0.00';
        foreach ($accounts as $account) {
            $totalBalance = bcadd($totalBalance, number_format((float) $account->balance, 2, '.', ''), 2);
        }

        $totalDebt = '0.00';
        $pendingInvoices = [];

        foreach ($invoices as $invoice) {
            $totalAmountStr = number_format((float) $invoice->total_amount, 2, '.', '');
            $paidAmountStr = number_format((float) $invoice->paid_amount, 2, '.', '');

            $pending = bcsub($totalAmountStr, $paidAmountStr, 2);

            if ($pending > 0) {
                $totalDebt = bcadd($totalDebt, $pending, 2);
                $pendingInvoices[] = [
                    'id' => $invoice->id,
                    'card_name' => $invoice->creditCard ? $invoice->creditCard->name : 'Cartão Excluído',
                    'due_date' => clone $invoice->due_date,
                    'total_amount' => $totalAmountStr,
                    'paid_amount' => $paidAmountStr,
                    'pending_amount' => $pending,
                ];
            }
        }

        $netWorth = bcsub($totalBalance, $totalDebt, 2);

        return [
            'accounts' => $accounts,
            'total_balance' => $totalBalance,
            'pending_invoices' => collect($pendingInvoices)->sortBy('due_date')->values()->all(),
            'total_debt' => $totalDebt,
            'net_worth' => $netWorth,
        ];
    }
}
