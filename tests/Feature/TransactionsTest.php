<?php

namespace Tests\Feature;

use App\Http\Livewire\Transactions;
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

class TransactionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
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

    public function test_guest_is_redirected_away_from_transactions_page(): void
    {
        $this->get('/lancamentos')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_transactions_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/lancamentos')->assertOk();
    }

    public function test_user_can_edit_their_own_transaction_and_preserve_type(): void
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

        Livewire::actingAs($user)->test(Transactions::class)
            ->call('edit', $transaction->id)
            ->set('amount', '250.00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'amount' => 250.00,
            'type' => 'income', // Type preserved
        ]);
    }

    public function test_cannot_edit_transaction_with_mismatched_category_type(): void
    {
        $user = User::factory()->create();
        $account = $this->createAccount($user);
        $expenseCategory = $this->createCategory($user, 'expense');

        $transaction = new Transaction([
            'account_id' => $account->id,
            'type' => 'income', // Original type is income
            'amount' => 100.00,
            'transaction_date' => now(),
            'status' => 'pending',
        ]);
        $transaction->user_id = $user->id;
        $transaction->save();

        Livewire::actingAs($user)->test(Transactions::class)
            ->call('edit', $transaction->id)
            ->set('categoryId', $expenseCategory->id) // Trying to set an expense category to an income transaction
            ->call('save')
            ->assertHasErrors(['categoryId']);
    }

    public function test_user_can_delete_their_own_transaction(): void
    {
        $user = User::factory()->create();
        $account = $this->createAccount($user);

        // Create a plain income transaction with no links to credit_card_invoice_payments.
        // This avoids the SQLite composite FK limitation on that table.
        $transaction = new Transaction([
            'account_id' => $account->id,
            'type'       => 'income',
            'amount'     => 100.00,
            'currency'   => 'BRL',
            'transaction_date' => now()->toDateString(),
            'status'     => 'posted',
        ]);
        $transaction->user_id = $user->id;
        $transaction->save();

        // Confirm no credit_card_invoice_payments row references this transaction.
        $this->assertDatabaseMissing('credit_card_invoice_payments', [
            'transaction_id' => $transaction->id,
        ]);

        Livewire::actingAs($user)->test(Transactions::class)
            ->call('delete', $transaction->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id,
        ]);
    }

    public function test_user_cannot_edit_another_users_transaction(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $account = $this->createAccount($owner);

        $transaction = new Transaction([
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 100.00,
            'transaction_date' => now(),
            'status' => 'pending',
        ]);
        $transaction->user_id = $owner->id;
        $transaction->save();

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($intruder)->test(Transactions::class)
            ->call('edit', $transaction->id);
    }
}
