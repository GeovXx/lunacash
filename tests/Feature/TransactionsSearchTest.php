<?php

namespace Tests\Feature;

use App\Http\Livewire\Transactions;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionsSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_filter_transactions_by_date()
    {
        $user = User::factory()->create();
        $account = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $oldDate = Carbon::now()->subMonths(2);
        $newDate = Carbon::now();

        $txOld = tap(Transaction::factory()->make([
            'account_id' => $account->id,
            'description' => 'Old Transaction',
            'transaction_date' => $oldDate,
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $txNew = tap(Transaction::factory()->make([
            'account_id' => $account->id,
            'description' => 'New Transaction',
            'transaction_date' => $newDate,
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        Livewire::actingAs($user)
            ->test(Transactions::class)
            ->set('filterStartDate', $newDate->copy()->startOfMonth()->toDateString())
            ->set('filterEndDate', $newDate->copy()->endOfMonth()->toDateString())
            ->assertSee('New Transaction')
            ->assertDontSee('Old Transaction');
    }

    public function test_it_can_filter_transactions_by_account()
    {
        $user = User::factory()->create();
        
        $account1 = tap(Account::factory()->make(['name' => 'Conta 1']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());
        $account2 = tap(Account::factory()->make(['name' => 'Conta 2']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        tap(Transaction::factory()->make([
            'account_id' => $account1->id,
            'description' => 'Tx Conta 1',
            'transaction_date' => Carbon::now(),
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        tap(Transaction::factory()->make([
            'account_id' => $account2->id,
            'description' => 'Tx Conta 2',
            'transaction_date' => Carbon::now(),
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        Livewire::actingAs($user)
            ->test(Transactions::class)
            ->set('filterAccountId', $account1->id)
            ->assertSee('Tx Conta 1')
            ->assertDontSee('Tx Conta 2');
    }

    public function test_it_can_filter_transactions_by_category()
    {
        $user = User::factory()->create();
        $account = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $category1 = tap(Category::factory()->make(['type' => 'expense']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());
        $category2 = tap(Category::factory()->make(['type' => 'expense']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        tap(Transaction::factory()->make([
            'account_id' => $account->id,
            'category_id' => $category1->id,
            'description' => 'Tx Cat 1',
            'transaction_date' => Carbon::now(),
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        tap(Transaction::factory()->make([
            'account_id' => $account->id,
            'category_id' => $category2->id,
            'description' => 'Tx Cat 2',
            'transaction_date' => Carbon::now(),
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        Livewire::actingAs($user)
            ->test(Transactions::class)
            ->set('filterCategoryId', $category1->id)
            ->assertSee('Tx Cat 1')
            ->assertDontSee('Tx Cat 2');
    }

    public function test_it_can_filter_transactions_by_search_term()
    {
        $user = User::factory()->create();
        $account = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        tap(Transaction::factory()->make([
            'account_id' => $account->id,
            'description' => 'Compra no Mercado',
            'transaction_date' => Carbon::now(),
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        tap(Transaction::factory()->make([
            'account_id' => $account->id,
            'description' => 'Pagamento de Luz',
            'transaction_date' => Carbon::now(),
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        Livewire::actingAs($user)
            ->test(Transactions::class)
            ->set('filterSearch', 'Mercado')
            ->assertSee('Compra no Mercado')
            ->assertDontSee('Pagamento de Luz');
    }
}
