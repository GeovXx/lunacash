<?php

namespace Tests\Feature;

use App\Livewire\Incomes;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\AccountTypeSeeder;
use Database\Seeders\CategorySeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IncomesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        $this->seed(AccountTypeSeeder::class);
        $this->seed(CategorySeeder::class);
    }

    private function createAccount(User $user): Account
    {
        $type = AccountType::first();
        $account = new Account([
            'account_type_id' => $type->id,
            'name' => 'Conta Corrente',
            'currency' => 'BRL',
            'status' => 'active',
        ]);
        $account->user_id = $user->id;
        $account->save();

        return $account;
    }

    private function createCategory(User $user, string $type = 'income'): Category
    {
        $category = new Category([
            'name' => 'Custom Category',
            'type' => $type,
        ]);
        $category->user_id = $user->id;
        $category->save();

        return $category;
    }

    public function test_guest_is_redirected_away_from_incomes_page(): void
    {
        $this->get('/receitas')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_incomes_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/receitas')->assertOk();
    }

    public function test_user_can_create_an_income(): void
    {
        $user = User::factory()->create();
        $account = $this->createAccount($user);

        Livewire::actingAs($user)->test(Incomes::class)
            ->set('accountId', $account->id)
            ->set('amount', '150.50')
            ->set('transactionDate', '2026-08-12')
            ->set('status', 'posted')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'type' => 'income',
            'amount' => 150.50,
        ]);
    }

    public function test_amount_must_be_greater_than_zero(): void
    {
        $user = User::factory()->create();
        $account = $this->createAccount($user);

        Livewire::actingAs($user)->test(Incomes::class)
            ->set('accountId', $account->id)
            ->set('amount', '-10.00')
            ->set('transactionDate', '2026-08-12')
            ->set('status', 'posted')
            ->call('save')
            ->assertHasErrors(['amount']);

        Livewire::actingAs($user)->test(Incomes::class)
            ->set('accountId', $account->id)
            ->set('amount', '0')
            ->set('transactionDate', '2026-08-12')
            ->set('status', 'posted')
            ->call('save')
            ->assertHasErrors(['amount']);
    }

    public function test_cannot_create_income_with_another_users_account(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherAccount = $this->createAccount($otherUser);

        Livewire::actingAs($user)->test(Incomes::class)
            ->set('accountId', $otherAccount->id)
            ->set('amount', '100.00')
            ->set('transactionDate', '2026-08-12')
            ->set('status', 'posted')
            ->call('save')
            ->assertHasErrors(['accountId']);
    }

    public function test_cannot_create_income_with_expense_category(): void
    {
        $user = User::factory()->create();
        $account = $this->createAccount($user);
        $expenseCategory = $this->createCategory($user, 'expense');

        Livewire::actingAs($user)->test(Incomes::class)
            ->set('accountId', $account->id)
            ->set('categoryId', $expenseCategory->id)
            ->set('amount', '100.00')
            ->set('transactionDate', '2026-08-12')
            ->set('status', 'posted')
            ->call('save')
            ->assertHasErrors(['categoryId']);
    }

    public function test_cannot_create_income_with_another_users_category(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $account = $this->createAccount($user);
        $otherCategory = $this->createCategory($otherUser, 'income');

        Livewire::actingAs($user)->test(Incomes::class)
            ->set('accountId', $account->id)
            ->set('categoryId', $otherCategory->id)
            ->set('amount', '100.00')
            ->set('transactionDate', '2026-08-12')
            ->set('status', 'posted')
            ->call('save')
            ->assertHasErrors(['categoryId']);
    }

    public function test_user_can_edit_their_own_income(): void
    {
        $user = User::factory()->create();
        $account = $this->createAccount($user);
        $transaction = new Transaction([
            'account_id' => $account->id,
            'type' => 'income',
            'amount' => 100.00,
            'transaction_date' => now(),
            'status' => 'pending',
        ]);
        $transaction->user_id = $user->id;
        $transaction->save();

        Livewire::actingAs($user)->test(Incomes::class)
            ->call('edit', $transaction->id)
            ->set('amount', '200.00')
            ->call('save');

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'amount' => 200.00,
        ]);
    }

    public function test_user_can_delete_their_own_income(): void
    {
        $user = User::factory()->create();
        $account = $this->createAccount($user);

        // Plain income with no credit_card_invoice_payments FK dependency
        $transaction = new Transaction([
            'account_id'       => $account->id,
            'type'             => 'income',
            'amount'           => 100.00,
            'currency'         => 'BRL',
            'transaction_date' => now()->toDateString(),
            'status'           => 'posted',
        ]);
        $transaction->user_id = $user->id;
        $transaction->save();

        $this->assertDatabaseMissing('credit_card_invoice_payments', [
            'transaction_id' => $transaction->id,
        ]);

        Livewire::actingAs($user)->test(Incomes::class)
            ->call('delete', $transaction->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id,
        ]);
    }

    public function test_user_cannot_edit_another_users_income(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $account = $this->createAccount($owner);
        $transaction = new Transaction([
            'account_id' => $account->id,
            'type' => 'income',
            'amount' => 100.00,
            'transaction_date' => now(),
            'status' => 'pending',
        ]);
        $transaction->user_id = $owner->id;
        $transaction->save();

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($intruder)->test(Incomes::class)
            ->call('edit', $transaction->id);
    }

    public function test_user_cannot_delete_another_users_income(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $account = $this->createAccount($owner);
        $transaction = new Transaction([
            'account_id' => $account->id,
            'type' => 'income',
            'amount' => 100.00,
            'transaction_date' => now(),
            'status' => 'pending',
        ]);
        $transaction->user_id = $owner->id;
        $transaction->save();

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($intruder)->test(Incomes::class)
            ->call('delete', $transaction->id);
    }
}
