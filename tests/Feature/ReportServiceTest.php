<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\CreditCardInvoice;
use App\Models\CreditCardTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_flow_report_calculates_correctly()
    {
        $user = User::factory()->create();
        
        $account = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        tap(Transaction::factory()->make([
            'account_id' => $account->id,
            'amount' => '1000.00',
            'type' => 'income',
            'transaction_date' => $start->copy()->addDays(1),
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        tap(Transaction::factory()->make([
            'account_id' => $account->id,
            'amount' => '200.00',
            'type' => 'expense',
            'transaction_date' => $start->copy()->addDays(2),
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        tap(Transaction::factory()->make([
            'account_id' => $account->id,
            'amount' => '300.00',
            'type' => 'payment',
            'transaction_date' => $start->copy()->addDays(3),
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $service = new ReportService();
        $report = $service->getCashFlowReport($user->id, $start, $end);

        $this->assertEquals('1000.00', $report['summary']['income']);
        $this->assertEquals('200.00', $report['summary']['expense']);
        $this->assertEquals('300.00', $report['summary']['payment']);
        $this->assertEquals('500.00', $report['summary']['total_out']);
        $this->assertEquals('500.00', $report['summary']['net_flow']);
        
        $this->assertCount(3, $report['transactions']);
    }

    public function test_category_report_aggregates_correctly()
    {
        $user = User::factory()->create();
        
        $account = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $user->id])->save());
        
        $card = tap(CreditCard::factory()->make(), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $categoryFood = tap(Category::factory()->make(['name' => 'Alimentação', 'type' => 'expense']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());
        $categoryTransport = tap(Category::factory()->make(['name' => 'Transporte', 'type' => 'expense']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        tap(Transaction::factory()->make([
            'account_id' => $account->id,
            'category_id' => $categoryFood->id,
            'amount' => '50.00',
            'type' => 'expense',
            'transaction_date' => $start->copy()->addDays(1),
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        tap(CreditCardTransaction::factory()->make([
            'credit_card_id' => $card->id,
            'category_id' => $categoryFood->id,
            'amount' => '150.00',
            'transaction_date' => $start->copy()->addDays(2),
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        tap(Transaction::factory()->make([
            'account_id' => $account->id,
            'category_id' => $categoryTransport->id,
            'amount' => '80.00',
            'type' => 'expense',
            'transaction_date' => $start->copy()->addDays(3),
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $service = new ReportService();
        $report = $service->getCategoryReport($user->id, $start, $end);

        $this->assertEquals('280.00', $report['total_consumo']);
        
        // Verifica ordenação por maior valor
        $this->assertEquals('Alimentação', $report['categories'][0]['name']);
        $this->assertEquals('200.00', $report['categories'][0]['amount']);
        
        $this->assertEquals('Transporte', $report['categories'][1]['name']);
        $this->assertEquals('80.00', $report['categories'][1]['amount']);
    }

    public function test_balances_report_calculates_correctly()
    {
        $user = User::factory()->create();
        
        $account1 = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $user->id])->save());
        
        tap(Transaction::factory()->make([
            'account_id' => $account1->id,
            'amount' => '1000.00',
            'type' => 'income',
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $account2 = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $user->id])->save());
        
        tap(Transaction::factory()->make([
            'account_id' => $account2->id,
            'amount' => '500.00',
            'type' => 'income',
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $card = tap(CreditCard::factory()->make(), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $invoice = tap(CreditCardInvoice::factory()->make([
            'credit_card_id' => $card->id,
            'total_amount' => '400.00',
            'paid_amount' => '100.00',
            'status' => 'open',
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $service = new ReportService();
        $report = $service->getBalancesReport($user->id);

        $this->assertEquals('1500.00', $report['total_balance']);
        $this->assertEquals('300.00', $report['total_debt']);
        $this->assertEquals('1200.00', $report['net_worth']);
        $this->assertCount(1, $report['pending_invoices']);
    }

    public function test_reports_isolate_user_data()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $accountA = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $userA->id])->save());
        $accountB = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $userB->id])->save());

        tap(Transaction::factory()->make([
            'account_id' => $accountA->id,
            'amount' => '1000.00',
            'type' => 'income',
        ]), fn($m) => $m->forceFill(['user_id' => $userA->id])->save());

        tap(Transaction::factory()->make([
            'account_id' => $accountB->id,
            'amount' => '500.00',
            'type' => 'income',
        ]), fn($m) => $m->forceFill(['user_id' => $userB->id])->save());

        $service = new ReportService();
        $reportA = $service->getBalancesReport($userA->id);
        $reportB = $service->getBalancesReport($userB->id);

        $this->assertEquals('1000.00', $reportA['total_balance']);
        $this->assertEquals('500.00', $reportB['total_balance']);
    }
}
