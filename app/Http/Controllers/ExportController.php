<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Export Cash Flow (Extrato) as CSV
     */
    public function exportCashFlow(Request $request)
    {
        $user = Auth::user();
        
        $start = $request->query('start') ? Carbon::parse($request->query('start')) : Carbon::now()->startOfMonth();
        $end = $request->query('end') ? Carbon::parse($request->query('end')) : Carbon::now()->endOfMonth();

        $filename = 'extrato_consolidado_' . $start->format('Ymd') . '_' . $end->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return new StreamedResponse(function () use ($user, $start, $end) {
            $handle = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // CSV Headers
            fputcsv($handle, ['Data', 'Referência/Descrição', 'Categoria', 'Conta', 'Tipo', 'Valor (R$)'], ';');

            $query = Transaction::with(['account', 'category'])
                ->where('user_id', $user->id)
                ->where('transaction_date', '>=', $start->toDateString())
                ->where('transaction_date', '<=', $end->toDateString() . ' 23:59:59')
                ->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc');

            $query->chunk(500, function ($transactions) use ($handle) {
                foreach ($transactions as $tx) {
                    $date = Carbon::parse($tx->transaction_date)->format('d/m/Y');
                    $description = $tx->description ?: $tx->reference;
                    $category = $tx->category ? $tx->category->name : 'Sem categoria';
                    $account = $tx->account ? $tx->account->name : 'N/A';
                    
                    $typeMap = [
                        'income' => 'Receita',
                        'expense' => 'Despesa',
                        'transfer' => 'Transferência',
                        'payment' => 'Pagamento'
                    ];
                    $type = $typeMap[$tx->type] ?? $tx->type;

                    // Format amount: 1234.56 -> 1234,56
                    $amount = number_format((float) $tx->amount, 2, ',', '');

                    fputcsv($handle, [$date, $description, $category, $account, $type, $amount], ';');
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export Category Consumption as CSV
     */
    public function exportCategories(Request $request)
    {
        $user = Auth::user();
        
        $start = $request->query('start') ? Carbon::parse($request->query('start')) : Carbon::now()->startOfMonth();
        $end = $request->query('end') ? Carbon::parse($request->query('end')) : Carbon::now()->endOfMonth();

        $filename = 'consumo_categorias_' . $start->format('Ymd') . '_' . $end->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return new StreamedResponse(function () use ($user, $start, $end) {
            $handle = fopen('php://output', 'w');
            
            // Add UTF-8 BOM
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Categoria', 'Valor Consumido (R$)'], ';');

            $reportData = $this->reportService->getCategoryReport($user->id, $start, $end);
            
            foreach ($reportData['categories'] as $cat) {
                $amount = number_format((float) $cat['amount'], 2, ',', '');
                fputcsv($handle, [$cat['name'], $amount], ';');
            }

            fputcsv($handle, ['Total Geral', number_format((float) $reportData['total_consumo'], 2, ',', '')], ';');

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export Balances as CSV
     */
    public function exportBalances(Request $request)
    {
        $user = Auth::user();
        $date = Carbon::now();

        $filename = 'posicao_patrimonial_' . $date->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return new StreamedResponse(function () use ($user) {
            $handle = fopen('php://output', 'w');
            
            // Add UTF-8 BOM
            fwrite($handle, "\xEF\xBB\xBF");

            $reportData = $this->reportService->getBalancesReport($user->id);

            // Accounts section
            fputcsv($handle, ['ATIVOS - Contas'], ';');
            fputcsv($handle, ['Instituição/Conta', 'Saldo (R$)'], ';');
            foreach ($reportData['accounts'] as $account) {
                $amount = number_format((float) $account->balance, 2, ',', '');
                fputcsv($handle, [$account->name, $amount], ';');
            }
            fputcsv($handle, ['Total de Ativos', number_format((float) $reportData['total_balance'], 2, ',', '')], ';');
            
            fputcsv($handle, [], ';'); // Empty line

            // Debts section
            fputcsv($handle, ['PASSIVOS - Faturas Pendentes'], ';');
            fputcsv($handle, ['Cartão', 'Vencimento', 'Pendente (R$)'], ';');
            foreach ($reportData['pending_invoices'] as $invoice) {
                $amount = number_format((float) $invoice['pending_amount'], 2, ',', '');
                $dueDate = Carbon::parse($invoice['due_date'])->format('d/m/Y');
                fputcsv($handle, [$invoice['card_name'], $dueDate, $amount], ';');
            }
            fputcsv($handle, ['Total de Passivos', number_format((float) $reportData['total_debt'], 2, ',', '')], ';');

            fputcsv($handle, [], ';'); // Empty line

            // Net worth
            fputcsv($handle, ['RESUMO'], ';');
            fputcsv($handle, ['Patrimônio Líquido', number_format((float) $reportData['net_worth'], 2, ',', '')], ';');

            fclose($handle);
        }, 200, $headers);
    }
}
