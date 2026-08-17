<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\CreditCardInvoice;
use App\Models\FinancialGoal;
use App\Models\FinancialObligation;
use App\Models\RecurringProfile;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CalendarService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class CalendarServiceTest extends TestCase
{
    use RefreshDatabase;

    private CalendarService $calendarService;

    private User $user;

    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calendarService = new CalendarService;
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        Auth::login($this->user);
    }

    public function test_transaction_aparece_e_isolamento_de_usuario()
    {
        $date = Carbon::today()->toDateString();
        AccountType::factory()->create(['key' => 'checking', 'nature' => 'asset']);
        $account = tap(Account::factory()->make()->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        $category = tap(Category::factory()->make()->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $tx1 = new Transaction([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'transaction_date' => $date,
            'amount' => '100.00',
            'type' => 'expense',
            'currency' => 'BRL',
            'status' => 'posted',
        ]);
        $tx1->user_id = $this->user->id;
        $tx1->save();

        Auth::login($this->otherUser);
        $account2 = tap(Account::factory()->make()->forceFill(['user_id' => $this->otherUser->id]), fn($m) => $m->save());
        $category2 = tap(Category::factory()->make()->forceFill(['user_id' => $this->otherUser->id]), fn($m) => $m->save());

        $tx2 = new Transaction([
            'account_id' => $account2->id,
            'category_id' => $category2->id,
            'transaction_date' => $date,
            'amount' => '200.00',
            'type' => 'expense',
            'currency' => 'BRL',
            'status' => 'posted',
        ]);
        $tx2->user_id = $this->otherUser->id;
        $tx2->save();
        Auth::login($this->user);

        $events = $this->calendarService->getEventsForPeriod($this->user->id, $date, $date);

        $this->assertCount(1, $events);
        $this->assertEquals('tx_'.$tx1->id, $events[0]['id']);
    }

    public function test_obrigacao_open_overdue_aparece_paid_cancelled_nao_aparece()
    {
        $date = Carbon::today()->toDateString();
        AccountType::factory()->create(['key' => 'checking', 'nature' => 'asset']);
        $account = tap(Account::factory()->make()->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        $category = tap(Category::factory()->make()->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $baseData = [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'due_date' => $date,
            'amount' => '50.00',
            'direction' => 'payable',
            'title' => 'Test',
            'currency' => 'BRL',
        ];

        $o1 = new FinancialObligation(array_merge($baseData, ['status' => 'open']));
        $o1->user_id = $this->user->id;
        $o1->save();
        $o2 = new FinancialObligation(array_merge($baseData, ['status' => 'paid']));
        $o2->user_id = $this->user->id;
        $o2->save();
        $o3 = new FinancialObligation(array_merge($baseData, ['status' => 'cancelled']));
        $o3->user_id = $this->user->id;
        $o3->save();
        $o4 = new FinancialObligation(array_merge($baseData, ['status' => 'open', 'due_date' => Carbon::yesterday()->toDateString()]));
        $o4->user_id = $this->user->id;
        $o4->save();

        $events = $this->calendarService->getEventsForPeriod($this->user->id, Carbon::yesterday()->toDateString(), $date);

        $this->assertCount(2, $events);
        $this->assertEquals('obligation', $events[0]['source_type']);
    }

    public function test_fatura_paid_nao_aparece_e_parcial_aparece_saldo_restante()
    {
        $date = Carbon::today()->toDateString();

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

        $inv1 = new CreditCardInvoice([
            'credit_card_id' => $card->id,
            'due_date' => $date,
            'closing_date' => $date,
            'period_start' => Carbon::parse($date)->subDays(60)->toDateString(),
            'period_end' => Carbon::parse($date)->subDays(31)->toDateString(),
            'status' => 'paid',
            'total_amount' => '100.00',
            'paid_amount' => '100.00',
        ]);
        $inv1->user_id = $this->user->id;
        $inv1->save();

        $inv2 = new CreditCardInvoice([
            'credit_card_id' => $card->id,
            'due_date' => Carbon::today()->addDay()->toDateString(),
            'closing_date' => Carbon::today()->addDay()->toDateString(),
            'period_start' => Carbon::parse($date)->subDays(30)->toDateString(),
            'period_end' => $date,
            'status' => 'closed',
            'total_amount' => '200.00',
            'paid_amount' => '50.00',
        ]);
        $inv2->user_id = $this->user->id;
        $inv2->save();

        $events = $this->calendarService->getEventsForPeriod($this->user->id, $date, Carbon::today()->addDay()->toDateString());

        $this->assertCount(1, $events);
        $this->assertEquals('inv_'.$inv2->id, $events[0]['id']);
        $this->assertEquals('150.00', $events[0]['amount']);
    }

    public function test_recurring_profile_ativo_gera_projecao()
    {
        $date = Carbon::today()->toDateString();
        AccountType::factory()->create(['key' => 'checking', 'nature' => 'asset']);
        $account = tap(Account::factory()->make()->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        $category = tap(Category::factory()->make()->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $profile = new RecurringProfile([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'name' => 'Netflix',
            'amount' => '40.00',
            'frequency' => 'monthly',
            'next_occurrence_date' => Carbon::today()->addDays(5)->toDateString(),
            'status' => 'active',
            'currency' => 'BRL',
        ]);
        $profile->user_id = $this->user->id;
        $profile->save();

        $events = $this->calendarService->getEventsForPeriod($this->user->id, $date, Carbon::today()->addDays(36)->toDateString());

        $this->assertCount(2, $events);
        $this->assertEquals('projected', $events[0]['status']);
    }

    public function test_recurring_profile_nao_projeta_se_ja_materializada()
    {
        $date = Carbon::today()->toDateString();
        AccountType::factory()->create(['key' => 'checking', 'nature' => 'asset']);
        $account = tap(Account::factory()->make()->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        $category = tap(Category::factory()->make()->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $profile = new RecurringProfile([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'name' => 'Netflix',
            'amount' => '40.00',
            'frequency' => 'monthly',
            'next_occurrence_date' => Carbon::today()->addMonths(1)->toDateString(),
            'status' => 'active',
            'currency' => 'BRL',
        ]);
        $profile->user_id = $this->user->id;
        $profile->save();

        $tx = new Transaction([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'transaction_date' => $date,
            'amount' => '40.00',
            'type' => 'expense',
            'recurring_profile_id' => $profile->id,
            'recurring_occurrence_date' => $date,
            'currency' => 'BRL',
            'status' => 'posted',
        ]);
        $tx->user_id = $this->user->id;
        $tx->save();

        $events = $this->calendarService->getEventsForPeriod($this->user->id, $date, Carbon::today()->addMonths(1)->toDateString());

        $this->assertCount(2, $events);
        $this->assertEquals('transaction', $events[0]['source_type']);
        $this->assertEquals('recurring', $events[1]['source_type']);
    }

    public function test_meta_aparece_como_marco_informativo()
    {
        $date = Carbon::today()->toDateString();

        $goal = new FinancialGoal([
            'name' => 'Viagem',
            'target_amount' => '5000.00',
            'current_amount' => '0.00',
            'target_date' => $date,
            'status' => 'active',
            'currency' => 'BRL',
        ]);
        $goal->user_id = $this->user->id;
        $goal->save();

        $events = $this->calendarService->getEventsForPeriod($this->user->id, Carbon::today()->subDay()->toDateString(), Carbon::today()->addDay()->toDateString());

        $this->assertCount(1, $events);
        $this->assertEquals('goal', $events[0]['source_type']);
        $this->assertEquals('neutral', $events[0]['direction']);
    }
}
