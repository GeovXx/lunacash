<?php

namespace Tests\Feature;

use App\Livewire\CreditCardInvoices;
use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\CreditCardInstallment;
use App\Models\CreditCardInvoice;
use App\Models\CreditCardInvoicePayment;
use App\Models\CreditCardTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Livewire\Livewire;
use Tests\TestCase;

/**
 * Cobre em profundidade o fluxo de pagamento de fatura de cartão de crédito,
 * incluindo múltiplos pagamentos parciais, rollback e não-duplicação de despesas.
 */
class CreditCardInvoicePaymentsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    private CreditCard $card;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->account = tap(Account::factory()->make()->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        // Give the account some balance to pay with
        tap(Transaction::factory()->make(['account_id'       => $this->account->id,
            'type'             => 'income',
            'amount'           => '5000.00',
            'currency'         => 'BRL',
            'transaction_date' => now()->toDateString(),
            'status'           => 'posted',])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $this->card = tap(CreditCard::factory()->make(['limit_amount'    => '1000.00',
            'available_limit' => '200.00' /* 800 used */])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function makeInvoice(string $total = '800.00', string $paid = '0.00', string $status = 'open'): CreditCardInvoice
    {
        return tap(CreditCardInvoice::factory()->make(['credit_card_id' => $this->card->id,
            'total_amount'   => $total,
            'paid_amount'    => $paid,
            'status'         => $status,])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
    }

    private function pay(CreditCardInvoice $invoice, string $amount): void
    {
        Livewire::actingAs($this->user)
            ->test(CreditCardInvoices::class)
            ->call('openPaymentModal', $invoice->id)
            ->set('accountId', $this->account->id)
            ->set('paymentAmount', $amount)
            ->set('paymentDate', now()->toDateString())
            ->call('payInvoice')
            ->assertHasNoErrors();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 1. Pagamento integral
    // ──────────────────────────────────────────────────────────────────────────

    public function test_full_payment_sets_status_to_paid_and_restores_full_limit(): void
    {
        $invoice = $this->makeInvoice('800.00', '0.00', 'open');

        $this->pay($invoice, '800.00');

        $invoice->refresh();
        $this->assertEquals('800.00', $invoice->paid_amount);
        $this->assertEquals('paid', $invoice->status);

        $this->card->refresh();
        $this->assertEquals('1000.00', $this->card->available_limit); // restored to cap

        $this->assertDatabaseHas('transactions', [
            'account_id' => $this->account->id,
            'type'       => 'payment',
            'amount'     => 800,
        ]);

        $this->assertDatabaseHas('credit_card_invoice_payments', [
            'invoice_id' => $invoice->id,
            'amount'     => 800,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 2. Pagamento parcial simples
    // ──────────────────────────────────────────────────────────────────────────

    public function test_partial_payment_sets_status_to_partially_paid(): void
    {
        $invoice = $this->makeInvoice('800.00', '0.00', 'open');

        $this->pay($invoice, '300.00');

        $invoice->refresh();
        $this->assertEquals('300.00', $invoice->paid_amount);
        $this->assertEquals('partially_paid', $invoice->status);

        // Remaining debt = 500
        $remaining = bcsub((string) $invoice->total_amount, (string) $invoice->paid_amount, 2);
        $this->assertEquals('500.00', $remaining);

        // Card limit restored by 300
        $this->card->refresh();
        $this->assertEquals('500.00', $this->card->available_limit); // 200 + 300

        // Account balance reduced by 300
        $this->assertEquals('4700.00', $this->account->fresh()->balance);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 3. Múltiplos pagamentos parciais → transição open → partially_paid → paid
    // ──────────────────────────────────────────────────────────────────────────

    public function test_multiple_partial_payments_transition_to_paid(): void
    {
        $invoice = $this->makeInvoice('900.00', '0.00', 'open');

        // First payment: R$300
        $this->pay($invoice, '300.00');

        $invoice->refresh();
        $this->assertEquals('300.00', $invoice->paid_amount);
        $this->assertEquals('partially_paid', $invoice->status);
        $this->assertEquals('600.00', bcsub((string) $invoice->total_amount, (string) $invoice->paid_amount, 2));

        // Second payment: R$400 (still partial)
        $this->pay($invoice, '400.00');

        $invoice->refresh();
        $this->assertEquals('700.00', $invoice->paid_amount);
        $this->assertEquals('partially_paid', $invoice->status);
        $this->assertEquals('200.00', bcsub((string) $invoice->total_amount, (string) $invoice->paid_amount, 2));

        // Third payment: R$200 (clears the debt)
        $this->pay($invoice, '200.00');

        $invoice->refresh();
        $this->assertEquals('900.00', $invoice->paid_amount);
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals('0.00', bcsub((string) $invoice->total_amount, (string) $invoice->paid_amount, 2));

        // All 3 invoice payments were recorded
        $this->assertEquals(3, CreditCardInvoicePayment::where('invoice_id', $invoice->id)->count());

        // All 3 bank transactions of type=payment were created
        $paymentTxCount = Transaction::where('user_id', $this->user->id)
            ->where('type', 'payment')
            ->count();
        $this->assertEquals(3, $paymentTxCount);

        // Account balance: 5000 - 300 - 400 - 200 = 4100
        $this->assertEquals('4100.00', $this->account->fresh()->balance);

        // Limit: was 200. 900 paid total. cap = 1000. So 200 + 900 = 1100 → capped at 1000
        $this->card->refresh();
        $this->assertEquals('1000.00', $this->card->available_limit);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 4. Saldo da conta decrementado exatamente pelo valor pago
    // ──────────────────────────────────────────────────────────────────────────

    public function test_account_balance_decremented_exactly(): void
    {
        $invoice = $this->makeInvoice('800.00', '0.00', 'open');

        $balanceBefore = $this->account->fresh()->balance;

        $this->pay($invoice, '250.00');

        $balanceAfter = $this->account->fresh()->balance;
        $this->assertEquals('250.00', bcsub((string) $balanceBefore, (string) $balanceAfter, 2));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 5. Pagamento NÃO duplica despesa no ReportService (categoria)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_paying_invoice_does_not_add_expense_to_category_report(): void
    {
        $category = tap(Category::factory()->make(['type' => 'expense'])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        // A credit card purchase: R$ 300 (already counted as "expense" at purchase time)
        $ccTx = tap(CreditCardTransaction::factory()->make(['credit_card_id'   => $this->card->id,
            'category_id'      => $category->id,
            'amount'           => '300.00',
            'transaction_date' => now()->toDateString(),])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $start = Carbon::today()->startOfMonth();
        $end   = Carbon::today()->endOfMonth();

        // Before paying: only the credit card purchase should show in the category report
        $reportService = new ReportService;
        $reportBefore  = $reportService->getCategoryReport($this->user->id, $start, $end);
        $totalBefore   = $reportBefore['total_consumo'];

        // Pay the invoice
        $invoice = $this->makeInvoice('300.00', '0.00', 'open');
        $this->pay($invoice, '300.00');

        // After paying: total_consumo must NOT increase (payment is not a new expense)
        $reportAfter = $reportService->getCategoryReport($this->user->id, $start, $end);
        $this->assertEquals($totalBefore, $reportAfter['total_consumo'],
            'Paying invoice must not add to category report total_consumo'
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 6. Pagamento NÃO aparece como "expense" no DashboardService
    // ──────────────────────────────────────────────────────────────────────────

    public function test_paying_invoice_does_not_count_as_monthly_expense(): void
    {
        $this->actingAs($this->user);

        $invoice = $this->makeInvoice('500.00', '0.00', 'open');

        $dashboardService = new DashboardService;
        $today = Carbon::today();

        $expensesBefore = $dashboardService->getMonthlyExpenses($this->user->id, $today);

        $this->pay($invoice, '500.00');

        $expensesAfter = $dashboardService->getMonthlyExpenses($this->user->id, $today);

        $this->assertEquals($expensesBefore, $expensesAfter,
            'Paying invoice (type=payment) must NOT be counted in getMonthlyExpenses'
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 7. Fatura "paid" não pode receber novo pagamento
    // ──────────────────────────────────────────────────────────────────────────

    public function test_paid_invoice_rejects_further_payment(): void
    {
        $invoice = $this->makeInvoice('300.00', '300.00', 'paid');

        Livewire::actingAs($this->user)
            ->test(CreditCardInvoices::class)
            ->call('openPaymentModal', $invoice->id)
            ->set('accountId', $this->account->id)
            ->set('paymentAmount', '100.00') // trying to pay a fully-paid invoice
            ->set('paymentDate', now()->toDateString())
            ->call('payInvoice')
            ->assertHasErrors(['paymentAmount']); // Remaining debt = 0, so >0 triggers rejection
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 8. Rollback: validação de sobrepagamento não altera nada
    //    O `payInvoice` usa DB::transaction internamente. Quando a validação de
    //    negócio falha (valor > dívida restante), a Exception é lançada DENTRO
    //    da transaction — garantindo que nenhum dado foi persistido.
    // ──────────────────────────────────────────────────────────────────────────

    public function test_overpayment_attempt_leaves_all_state_unchanged(): void
    {
        $invoice = $this->makeInvoice('500.00', '200.00', 'partially_paid');
        // Remaining debt = 300.00
        $remainingDebt = bcsub((string) $invoice->total_amount, (string) $invoice->paid_amount, 2);
        $this->assertEquals('300.00', $remainingDebt);

        $balanceBefore      = $this->account->fresh()->balance;
        $limitBefore        = $this->card->fresh()->available_limit;
        $paidBefore         = $invoice->fresh()->paid_amount;
        $statusBefore       = $invoice->fresh()->status;
        $paymentCountBefore = CreditCardInvoicePayment::where('invoice_id', $invoice->id)->count();
        $txCountBefore      = Transaction::where('user_id', $this->user->id)->where('type', 'payment')->count();

        // Attempt to pay R$ 400 (exceeds remaining R$ 300)
        Livewire::actingAs($this->user)
            ->test(CreditCardInvoices::class)
            ->call('openPaymentModal', $invoice->id)
            ->set('accountId', $this->account->id)
            ->set('paymentAmount', '400.00')
            ->set('paymentDate', now()->toDateString())
            ->call('payInvoice')
            ->assertHasErrors(['paymentAmount']); // validation failed → rollback

        // All state must be exactly as before
        $this->assertEquals($balanceBefore, $this->account->fresh()->balance,
            'Account balance must be unchanged after failed overpayment'
        );
        $this->assertEquals($limitBefore, $this->card->fresh()->available_limit,
            'Card limit must be unchanged after failed overpayment'
        );
        $this->assertEquals($paidBefore, $invoice->fresh()->paid_amount,
            'Invoice paid_amount must be unchanged after failed overpayment'
        );
        $this->assertEquals($statusBefore, $invoice->fresh()->status,
            'Invoice status must be unchanged after failed overpayment'
        );
        $this->assertEquals($paymentCountBefore,
            CreditCardInvoicePayment::where('invoice_id', $invoice->id)->count(),
            'No CreditCardInvoicePayment must be created after failed overpayment'
        );
        $this->assertEquals($txCountBefore,
            Transaction::where('user_id', $this->user->id)->where('type', 'payment')->count(),
            'No payment Transaction must be created after failed overpayment'
        );
    }
}

