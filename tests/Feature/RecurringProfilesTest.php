<?php

namespace Tests\Feature;

use App\Livewire\RecurringProfiles;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Category;
use App\Models\RecurringProfile;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;

class RecurringProfilesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    private Category $categoryIncome;

    private Category $categoryExpense;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        auth()->login($this->user);

        $accountType = AccountType::create([
            'name' => 'Checking',
            'key' => 'checking',
            'nature' => 'asset',
        ]);

        $this->account = tap((new Account(['account_type_id' => $accountType->id,
            'name' => 'Test Account',]))->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $this->categoryIncome = tap((new Category(['name' => 'Test Income',
            'type' => 'income',]))->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $this->categoryExpense = tap((new Category(['name' => 'Test Expense',
            'type' => 'expense',]))->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
    }

    // 1. Categoria incompatível com o type falha
    public function test_category_incompatible_with_type_fails()
    {
        Livewire::actingAs($this->user)
            ->test(RecurringProfiles::class)
            ->set('type', 'income')
            ->set('categoryId', $this->categoryExpense->id)
            ->set('accountId', $this->account->id)
            ->set('name', 'Test')
            ->set('frequency', 'monthly')
            ->set('amount', '100.00')
            ->set('nextOccurrenceDate', '2026-08-01')
            ->call('save')
            ->assertHasErrors(['categoryId']);
    }

    // 2. Usar conta de outro usuário falha
    public function test_cannot_use_other_user_account()
    {
        $otherUser = User::factory()->create();
        auth()->login($otherUser);

        $accountType = AccountType::first();
        $otherAccount = tap((new Account(['account_type_id' => $accountType->id,
            'name' => 'Other Account',]))->forceFill(['user_id' => $otherUser->id]), fn($m) => $m->save());

        auth()->login($this->user);

        Livewire::actingAs($this->user)
            ->test(RecurringProfiles::class)
            ->set('type', 'income')
            ->set('categoryId', $this->categoryIncome->id)
            ->set('accountId', $otherAccount->id)
            ->set('name', 'Test')
            ->set('frequency', 'monthly')
            ->set('amount', '100.00')
            ->set('nextOccurrenceDate', '2026-08-01')
            ->call('save')
            ->assertHasErrors(['accountId']);
    }

    // 3. Usar categoria de outro usuário falha
    public function test_cannot_use_other_user_category()
    {
        $otherUser = User::factory()->create();
        auth()->login($otherUser);

        $otherCategory = tap((new Category(['name' => 'Other Category',
            'type' => 'income',]))->forceFill(['user_id' => $otherUser->id]), fn($m) => $m->save());

        auth()->login($this->user);

        Livewire::actingAs($this->user)
            ->test(RecurringProfiles::class)
            ->set('type', 'income')
            ->set('categoryId', $otherCategory->id)
            ->set('accountId', $this->account->id)
            ->set('name', 'Test')
            ->set('frequency', 'monthly')
            ->set('amount', '100.00')
            ->set('nextOccurrenceDate', '2026-08-01')
            ->call('save')
            ->assertHasErrors(['categoryId']);
    }

    // 4. Perfil pausado não gera ocorrências
    public function test_paused_profile_does_not_generate_occurrences()
    {
        $profile = tap(tap(new RecurringProfile(['account_id' => $this->account->id,
            'category_id' => $this->categoryExpense->id,
            'type' => 'expense',
            'name' => 'Netflix',
            'frequency' => 'monthly',
            'amount' => '39.90',
            'currency' => 'BRL',
            'next_occurrence_date' => today()->subDays(5),
            'status' => 'paused',]))->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        Artisan::call('app:generate-recurring-transactions');

        $this->assertDatabaseMissing('transactions', [
            'recurring_profile_id' => $profile->id,
        ]);

        $profile->refresh();
        $this->assertEquals(today()->subDays(5)->toDateString(), $profile->next_occurrence_date->toDateString());
    }

    // 5. Perfil cancelado não gera ocorrências
    public function test_cancelled_profile_does_not_generate_occurrences()
    {
        $profile = tap(tap(new RecurringProfile(['account_id' => $this->account->id,
            'category_id' => $this->categoryExpense->id,
            'type' => 'expense',
            'name' => 'Netflix',
            'frequency' => 'monthly',
            'amount' => '39.90',
            'currency' => 'BRL',
            'next_occurrence_date' => today()->subDays(5),
            'status' => 'cancelled',]))->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        Artisan::call('app:generate-recurring-transactions');

        $this->assertDatabaseMissing('transactions', [
            'recurring_profile_id' => $profile->id,
        ]);
    }

    // 6. Recorrência mensal iniciada em 31/01 (Teste clássico de Leap/Short month)
    public function test_monthly_recurrence_started_on_31st()
    {
        Carbon::setTestNow('2024-04-15'); // Ano bissexto para testar Fev 29

        $profile = tap((new RecurringProfile(['account_id' => $this->account->id,
            'category_id' => $this->categoryExpense->id,
            'type' => 'expense',
            'name' => 'Assinatura',
            'frequency' => 'monthly',
            'amount' => '100.00',
            'currency' => 'BRL',
            'next_occurrence_date' => '2024-01-31',
            'status' => 'active',
            'metadata' => ['start_day' => 31],]))->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        Artisan::call('app:generate-recurring-transactions');

        // Deverá ter gerado para: 31/01, 29/02, 31/03.
        $this->assertDatabaseCount('transactions', 3);

        $this->assertDatabaseHas('transactions', ['recurring_occurrence_date' => '2024-01-31']);
        $this->assertDatabaseHas('transactions', ['recurring_occurrence_date' => '2024-02-29']);
        $this->assertDatabaseHas('transactions', ['recurring_occurrence_date' => '2024-03-31']);

        $profile->refresh();
        $this->assertEquals('2024-04-30', $profile->next_occurrence_date->toDateString()); // Próximo mês, abril só tem 30
    }

    // 7. Recorrência com end_date definido cessa a geração corretamente
    // 8. Nenhuma ocorrência é gerada após o end_date (status muda para completed)
    public function test_recurrence_with_end_date_completes_correctly()
    {
        Carbon::setTestNow('2024-05-01');

        $profile = tap((new RecurringProfile(['account_id' => $this->account->id,
            'category_id' => $this->categoryExpense->id,
            'type' => 'expense',
            'name' => 'Parcela',
            'frequency' => 'monthly',
            'amount' => '100.00',
            'currency' => 'BRL',
            'next_occurrence_date' => '2024-01-01',
            'end_date' => '2024-03-01', // 3 parcelas apenas
            'status' => 'active',
            'metadata' => ['start_day' => 1],]))->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        Artisan::call('app:generate-recurring-transactions');

        $this->assertDatabaseCount('transactions', 3);
        $this->assertDatabaseHas('transactions', ['recurring_occurrence_date' => '2024-01-01']);
        $this->assertDatabaseHas('transactions', ['recurring_occurrence_date' => '2024-02-01']);
        $this->assertDatabaseHas('transactions', ['recurring_occurrence_date' => '2024-03-01']);
        $this->assertDatabaseMissing('transactions', ['recurring_occurrence_date' => '2024-04-01']);

        $profile->refresh();
        $this->assertEquals('completed', $profile->status);
    }

    // 9. Catch-up processa múltiplas ocorrências acumuladas no passado de forma estrita
    public function test_catch_up_processes_multiple_occurrences_strictly()
    {
        Carbon::setTestNow('2024-01-20');

        $profile = tap((new RecurringProfile(['account_id' => $this->account->id,
            'category_id' => $this->categoryExpense->id,
            'type' => 'expense',
            'name' => 'Semanal',
            'frequency' => 'weekly',
            'amount' => '10.00',
            'currency' => 'BRL',
            'next_occurrence_date' => '2024-01-01',
            'status' => 'active',]))->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        Artisan::call('app:generate-recurring-transactions');

        // 01/01, 08/01, 15/01 (3 ocorrências)
        $this->assertDatabaseCount('transactions', 3);

        $profile->refresh();
        $this->assertEquals('2024-01-22', $profile->next_occurrence_date->toDateString());
    }

    // 11. Duplicação protegida matematicamente pela UNIQUE constraint
    // 12. Execução repetida do comando simultaneamente não polui o saldo
    public function test_unique_constraint_prevents_duplication()
    {
        $profile = tap(tap(new RecurringProfile(['account_id' => $this->account->id,
            'category_id' => $this->categoryExpense->id,
            'type' => 'expense',
            'name' => 'Assinatura',
            'frequency' => 'monthly',
            'amount' => '100.00',
            'currency' => 'BRL',
            'next_occurrence_date' => today()->subDay(),
            'status' => 'active',]))->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        // Simula a transação já tendo sido criada (como se outro worker tivesse feito)
        tap(tap(new Transaction(['account_id' => $this->account->id,
            'category_id' => $this->categoryExpense->id,
            'type' => 'expense',
            'amount' => '100.00',
            'currency' => 'BRL',
            'transaction_date' => today()->subDay(),
            'status' => 'posted',
            'recurring_profile_id' => $profile->id,
            'recurring_occurrence_date' => today()->subDay()->toDateString(),]))->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $this->assertDatabaseCount('transactions', 1);

        // Executa o comando. Ele deve tentar inserir a mesma ocorrência, estourar a constraint Unique,
        // perceber que foi duplicação, e APENAS avançar a data sem duplicar a transação.
        Artisan::call('app:generate-recurring-transactions');

        $this->assertDatabaseCount('transactions', 1); // Não gerou duplicata

        $profile->refresh();
        // A data deve ter avançado para o mês seguinte
        $this->assertTrue($profile->next_occurrence_date->isAfter(today()));
    }
}
