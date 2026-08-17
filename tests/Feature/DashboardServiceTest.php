<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\CreditCardInvoice;
use App\Models\CreditCardTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private DashboardService $dashboardService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->dashboardService = new DashboardService;
    }

    public function test_get_accounts_balance_sums_active_accounts_correctly()
    {
        // Two active accounts
        $a1 = tap(Account::factory()->make(['status' => 'active'])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        $a2 = tap(Account::factory()->make(['status' => 'active'])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        $a3 = tap(Account::factory()->make(['status' => 'closed'])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $category = tap(Category::factory()->make()->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        // Mock balances by inserting transactions
        tap(Transaction::factory()->make(['account_id' => $a1->id, 'type' => 'income', 'amount' => '500.00'])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        tap(Transaction::factory()->make(['account_id' => $a2->id, 'type' => 'expense', 'amount' => '100.00'])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        tap(Transaction::factory()->make(['account_id' => $a3->id, 'type' => 'income', 'amount' => '1000.00'])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        // Only active accounts: 500 - 100 = 400
        $balance = $this->dashboardService->getAccountsBalance($this->user->id);
        $this->assertEquals('400.00', $balance);
    }

    public function test_monthly_incomes_excludes_payments_and_transfers()
    {
        $date = Carbon::today();
        $account = tap(Account::factory()->make()->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        $category = tap(Category::factory()->make()->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        tap(Transaction::factory()->make(['account_id' => $account->id, 'type' => 'income', 'amount' => '200.00', 'transaction_date' => $date])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        tap(Transaction::factory()->make(['account_id' => $account->id, 'type' => 'income', 'amount' => '300.00', 'transaction_date' => $date])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        // This is next month, should be ignored
        tap(Transaction::factory()->make(['account_id' => $account->id, 'type' => 'income', 'amount' => '100.00', 'transaction_date' => $date->copy()->addMonth()])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        // These have different types, shouldn't be counted in income
        tap(Transaction::factory()->make(['account_id' => $account->id, 'type' => 'expense', 'amount' => '50.00', 'transaction_date' => $date])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        tap(Transaction::factory()->make(['account_id' => $account->id, 'type' => 'payment', 'amount' => '10.00', 'transaction_date' => $date])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $incomes = $this->dashboardService->getMonthlyIncomes($this->user->id, $date);
        $this->assertEquals('500.00', $incomes);
    }

    public function test_monthly_expenses_excludes_payments_and_transfers()
    {
        $date = Carbon::today();
        $account = tap(Account::factory()->make()->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        tap(Transaction::factory()->make(['account_id' => $account->id, 'type' => 'expense', 'amount' => '100.00', 'transaction_date' => $date])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        tap(Transaction::factory()->make(['account_id' => $account->id, 'type' => 'expense', 'amount' => '50.00', 'transaction_date' => $date])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        // Payment and Transfer shouldn't be counted as expense
        tap(Transaction::factory()->make(['account_id' => $account->id, 'type' => 'payment', 'amount' => '500.00', 'transaction_date' => $date])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        // Should be ignored (wrong month)
        tap(Transaction::factory()->make(['account_id' => $account->id, 'type' => 'expense', 'amount' => '10.00', 'transaction_date' => $date->copy()->subMonth()])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $expenses = $this->dashboardService->getMonthlyExpenses($this->user->id, $date);
        $this->assertEquals('150.00', $expenses);
    }

    public function test_pending_invoices_total_only_sums_unpaid_amounts()
    {
        $card = new CreditCard([
            'name' => 'Card',
            'issuer' => 'Bank',
            'last_digits' => '1234',
            'limit_amount' => 1000,
            'available_limit' => 1000,
            'currency' => 'BRL',
            'statement_day' => 1,
            'due_day' => 10,
            'status' => 'active',
        ]);
        $card->user_id = $this->user->id;
        $card->save();

        // 1. Open invoice, fully unpaid -> pending 150
        $i1 = new CreditCardInvoice(['credit_card_id' => $card->id, 'period_start' => Carbon::now()->subDays(30), 'period_end' => Carbon::now(), 'due_date' => Carbon::now()->addDays(5), 'closing_date' => Carbon::now(), 'total_amount' => '150.00', 'paid_amount' => '0.00', 'status' => 'open']);
        $i1->user_id = $this->user->id;
        $i1->save();

        // 2. Closed invoice, partially paid -> pending 50
        $i2 = new CreditCardInvoice(['credit_card_id' => $card->id, 'period_start' => Carbon::now()->subDays(60), 'period_end' => Carbon::now()->subDays(31), 'due_date' => Carbon::now()->subDays(10), 'closing_date' => Carbon::now()->subDays(15), 'total_amount' => '200.00', 'paid_amount' => '150.00', 'status' => 'closed']);
        $i2->user_id = $this->user->id;
        $i2->save();

        // 3. Paid invoice -> pending 0
        $i3 = new CreditCardInvoice(['credit_card_id' => $card->id, 'period_start' => Carbon::now()->subDays(90), 'period_end' => Carbon::now()->subDays(61), 'due_date' => Carbon::now()->subDays(40), 'closing_date' => Carbon::now()->subDays(45), 'total_amount' => '100.00', 'paid_amount' => '100.00', 'status' => 'paid']);
        $i3->user_id = $this->user->id;
        $i3->save();

        $totalPending = $this->dashboardService->getPendingInvoicesTotal($this->user->id);
        $this->assertEquals('200.00', $totalPending); // 150 + 50
    }

    public function test_isolation_between_users()
    {
        $otherUser = User::factory()->create();
        $accountOther = tap(Account::factory()->make(['status' => 'active'])->forceFill(['user_id' => $otherUser->id]), fn($m) => $m->save());
        tap(Transaction::factory()->make(['account_id' => $accountOther->id, 'type' => 'income', 'amount' => '500.00', 'transaction_date' => Carbon::today()])->forceFill(['user_id' => $otherUser->id]), fn($m) => $m->save());

        $incomesUser1 = $this->dashboardService->getMonthlyIncomes($this->user->id, Carbon::today());
        $incomesUser2 = $this->dashboardService->getMonthlyIncomes($otherUser->id, Carbon::today());

        $this->assertEquals('0.00', $incomesUser1);
        $this->assertEquals('500.00', $incomesUser2);
    }

    public function test_get_cash_flow_evolution_ignores_payments_transfers_and_credit_cards()
    {
        $account = tap(Account::factory()->make()->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        $card = tap(CreditCard::factory()->make()->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $thisMonth = Carbon::today();

        // Income + Expense
        tap(Transaction::factory()->make(['account_id' => $account->id, 'type' => 'income', 'amount' => '1000.00', 'transaction_date' => $thisMonth])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        tap(Transaction::factory()->make(['account_id' => $account->id, 'type' => 'expense', 'amount' => '400.00', 'transaction_date' => $thisMonth])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        // Ignored
        tap(Transaction::factory()->make(['account_id' => $account->id, 'type' => 'payment', 'amount' => '200.00', 'transaction_date' => $thisMonth])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        tap(Transaction::factory()->make(['account_id' => $account->id, 'type' => 'transfer', 'amount' => '100.00', 'transaction_date' => $thisMonth])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        tap(CreditCardTransaction::factory()->make(['credit_card_id' => $card->id, 'amount' => '500.00', 'transaction_date' => $thisMonth])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $evolution = $this->dashboardService->getCashFlowEvolution($this->user->id, 1);

        $this->assertCount(1, $evolution['labels']);
        $this->assertEquals(1000.0, $evolution['incomes'][0]);
        $this->assertEquals(400.0, $evolution['expenses'][0]);
    }

    public function test_get_expenses_by_category_combines_transactions_and_credit_card()
    {
        $account = tap(Account::factory()->make()->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        $card = tap(CreditCard::factory()->make()->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        $cat1 = tap(Category::factory()->make(['name' => 'Food'])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        $cat2 = tap(Category::factory()->make(['name' => 'Transport'])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        $cat3 = tap(Category::factory()->make(['name' => 'Zero'])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $thisMonth = Carbon::today();

        // Cat1: 100 debit, 50 credit
        tap(Transaction::factory()->make(['account_id' => $account->id, 'category_id' => $cat1->id, 'type' => 'expense', 'amount' => '100.00', 'transaction_date' => $thisMonth])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        tap(CreditCardTransaction::factory()->make(['credit_card_id' => $card->id, 'category_id' => $cat1->id, 'amount' => '50.00', 'transaction_date' => $thisMonth])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        // Cat2: 200 debit, 0 credit
        tap(Transaction::factory()->make(['account_id' => $account->id, 'category_id' => $cat2->id, 'type' => 'expense', 'amount' => '200.00', 'transaction_date' => $thisMonth])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        // Cat3: 0 (should not appear)

        // Uncategorized: 30 credit
        tap(CreditCardTransaction::factory()->make(['credit_card_id' => $card->id, 'category_id' => null, 'amount' => '30.00', 'transaction_date' => $thisMonth])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        // Ignored: Payment (even if it has category)
        tap(Transaction::factory()->make(['account_id' => $account->id, 'category_id' => $cat1->id, 'type' => 'payment', 'amount' => '500.00', 'transaction_date' => $thisMonth])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $categories = $this->dashboardService->getExpensesByCategory($this->user->id, $thisMonth);

        $this->assertCount(3, $categories['labels']);
        $this->assertCount(3, $categories['series']);

        // Check values (order is descending by amount)
        // 200 (Transport), 150 (Food), 30 (Outros/Uncategorized)

        $this->assertEquals('Transport', $categories['labels'][0]);
        $this->assertEquals(200.0, $categories['series'][0]);

        $this->assertEquals('Food', $categories['labels'][1]);
        $this->assertEquals(150.0, $categories['series'][1]);

        $this->assertEquals('Outros', $categories['labels'][2]);
        $this->assertEquals(30.0, $categories['series'][2]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Regressão Etapa 32 — getAccountsBalance edge cases
    // ──────────────────────────────────────────────────────────────────────────

    public function test_get_accounts_balance_returns_zero_when_no_active_accounts(): void
    {
        // User with no accounts at all
        $balance = $this->dashboardService->getAccountsBalance($this->user->id);
        $this->assertEquals('0.00', $balance);
    }

    public function test_get_accounts_balance_ignores_closed_accounts(): void
    {
        $account = tap(Account::factory()->make(['status' => 'closed'])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        $category = tap(Category::factory()->make()->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        tap(Transaction::factory()->make(['account_id' => $account->id,
            'type'       => 'income',
            'amount'     => '999.00',])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        // Closed account balance must NOT be counted
        $balance = $this->dashboardService->getAccountsBalance($this->user->id);
        $this->assertEquals('0.00', $balance);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Regressão Etapa 32 — getMonthlyIncomes / getMonthlyExpenses sem dados
    // ──────────────────────────────────────────────────────────────────────────

    public function test_get_monthly_incomes_returns_zero_when_no_data(): void
    {
        $result = $this->dashboardService->getMonthlyIncomes($this->user->id, Carbon::today());
        $this->assertEquals('0.00', $result);
    }

    public function test_get_monthly_expenses_returns_zero_when_no_data(): void
    {
        $result = $this->dashboardService->getMonthlyExpenses($this->user->id, Carbon::today());
        $this->assertEquals('0.00', $result);
    }

    public function test_get_monthly_expenses_excludes_payment_type(): void
    {
        $account = tap(Account::factory()->make()->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        tap(Transaction::factory()->make(['account_id'       => $account->id,
            'type'             => 'payment',
            'amount'           => '500.00',
            'transaction_date' => Carbon::today(),])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $result = $this->dashboardService->getMonthlyExpenses($this->user->id, Carbon::today());
        $this->assertEquals('0.00', $result, 'payment type must not count as expense');
    }

    public function test_get_monthly_incomes_excludes_transfer_type(): void
    {
        $account = tap(Account::factory()->make()->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        tap(Transaction::factory()->make(['account_id'       => $account->id,
            'type'             => 'transfer',
            'amount'           => '200.00',
            'transaction_date' => Carbon::today(),])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $result = $this->dashboardService->getMonthlyIncomes($this->user->id, Carbon::today());
        $this->assertEquals('0.00', $result, 'transfer type must not count as income');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Regressão Etapa 32 — getCashFlowEvolution com meses sem dados → zeros
    // ──────────────────────────────────────────────────────────────────────────

    public function test_get_cash_flow_evolution_returns_zeros_for_empty_months(): void
    {
        $evolution = $this->dashboardService->getCashFlowEvolution($this->user->id, 3);

        $this->assertCount(3, $evolution['labels']);
        $this->assertCount(3, $evolution['incomes']);
        $this->assertCount(3, $evolution['expenses']);

        foreach ($evolution['incomes'] as $income) {
            $this->assertEquals(0.0, $income, 'Empty month must return 0.0 for incomes');
        }
        foreach ($evolution['expenses'] as $expense) {
            $this->assertEquals(0.0, $expense, 'Empty month must return 0.0 for expenses');
        }
    }

    public function test_get_cash_flow_evolution_with_data_only_in_middle_month(): void
    {
        $account = tap(Account::factory()->make()->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        // Insert data only 1 month ago (middle of a 3-month window)
        $middleMonth = Carbon::today()->startOfMonth()->subMonth();

        tap(Transaction::factory()->make(['account_id'       => $account->id,
            'type'             => 'income',
            'amount'           => '1000.00',
            'transaction_date' => $middleMonth->toDateString(),])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        tap(Transaction::factory()->make(['account_id'       => $account->id,
            'type'             => 'expense',
            'amount'           => '400.00',
            'transaction_date' => $middleMonth->toDateString(),])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $evolution = $this->dashboardService->getCashFlowEvolution($this->user->id, 3);

        $this->assertCount(3, $evolution['labels']);

        // Middle month (index 1): has data
        $this->assertEquals(1000.0, $evolution['incomes'][1]);
        $this->assertEquals(400.0, $evolution['expenses'][1]);

        // First month (index 0): no data → zeros
        $this->assertEquals(0.0, $evolution['incomes'][0]);
        $this->assertEquals(0.0, $evolution['expenses'][0]);

        // Last month (index 2 = current month): no data → zeros
        $this->assertEquals(0.0, $evolution['incomes'][2]);
        $this->assertEquals(0.0, $evolution['expenses'][2]);
    }
}

