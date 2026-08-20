<?php

namespace Tests\Feature;

use App\Livewire\Transfers;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\User;
use Database\Seeders\AccountTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TransfersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccountTypeSeeder::class);
    }

    private function createAccount(User $user, string $name = 'Conta Corrente'): Account
    {
        $type = AccountType::first();
        $account = new Account([
            'account_type_id' => $type->id,
            'name' => $name,
            'currency' => 'BRL',
            'status' => 'active',
        ]);
        $account->user_id = $user->id;
        $account->save();

        return $account;
    }

    public function test_authenticated_user_can_view_transfers_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/transferencias')->assertOk();
    }

    public function test_user_can_create_transfer(): void
    {
        $user = User::factory()->create();
        $fromAccount = $this->createAccount($user, 'Conta A');
        $toAccount = $this->createAccount($user, 'Conta B');

        Livewire::actingAs($user)->test(Transfers::class)
            ->set('fromAccountId', $fromAccount->id)
            ->set('toAccountId', $toAccount->id)
            ->set('amount', '500.00')
            ->set('transferDate', now()->toDateString())
            ->set('status', 'completed')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('transfers', [
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
            'amount' => 500.00,
            'user_id' => $user->id,
        ]);
    }

    public function test_cannot_transfer_to_same_account(): void
    {
        $user = User::factory()->create();
        $account = $this->createAccount($user, 'Conta Única');

        Livewire::actingAs($user)->test(Transfers::class)
            ->set('fromAccountId', $account->id)
            ->set('toAccountId', $account->id)
            ->set('amount', '500.00')
            ->set('transferDate', now()->toDateString())
            ->set('status', 'completed')
            ->call('save')
            ->assertHasErrors(['toAccountId']);
    }

    public function test_cannot_transfer_to_another_users_account(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $fromAccount = $this->createAccount($user, 'Conta A');
        $toAccount = $this->createAccount($otherUser, 'Conta B');

        Livewire::actingAs($user)->test(Transfers::class)
            ->set('fromAccountId', $fromAccount->id)
            ->set('toAccountId', $toAccount->id)
            ->set('amount', '500.00')
            ->call('save')
            ->assertHasErrors(['toAccountId']);
    }

    public function test_cannot_use_negative_or_zero_amount(): void
    {
        $user = User::factory()->create();
        $fromAccount = $this->createAccount($user, 'Conta A');
        $toAccount = $this->createAccount($user, 'Conta B');

        Livewire::actingAs($user)->test(Transfers::class)
            ->set('fromAccountId', $fromAccount->id)
            ->set('toAccountId', $toAccount->id)
            ->set('amount', '-100.00')
            ->call('save')
            ->assertHasErrors(['amount']);

        Livewire::actingAs($user)->test(Transfers::class)
            ->set('amount', '0')
            ->call('save')
            ->assertHasErrors(['amount']);
    }

    public function test_balance_is_correctly_calculated_with_transfers_and_transactions(): void
    {
        $user = User::factory()->create();
        $accountA = $this->createAccount($user, 'Conta A');
        $accountB = $this->createAccount($user, 'Conta B');

        // Initial income in A: 1000
        $transaction = new Transaction([
            'account_id' => $accountA->id,
            'type' => 'income',
            'amount' => 1000.00,
            'transaction_date' => now(),
            'status' => 'posted',
        ]);
        $transaction->user_id = $user->id;
        $transaction->save();

        // Transfer A -> B: 200
        Livewire::actingAs($user)->test(Transfers::class)
            ->set('fromAccountId', $accountA->id)
            ->set('toAccountId', $accountB->id)
            ->set('amount', '200.00')
            ->set('transferDate', now()->toDateString())
            ->set('status', 'completed')
            ->call('save');

        $this->assertEquals('800.00', $accountA->fresh()->balance);
        $this->assertEquals('200.00', $accountB->fresh()->balance);

        // Assert no artificial transaction was created
        $this->assertEquals(1, Transaction::count());
    }

    public function test_deleting_a_transfer_restores_balances(): void
    {
        $user = User::factory()->create();
        $accountA = $this->createAccount($user, 'Conta A');
        $accountB = $this->createAccount($user, 'Conta B');

        $transaction = new Transaction([
            'account_id' => $accountA->id,
            'type' => 'income',
            'amount' => 1000.00,
            'transaction_date' => now(),
            'status' => 'posted',
        ]);
        $transaction->user_id = $user->id;
        $transaction->save();

        $transfer = new Transfer([
            'from_account_id' => $accountA->id,
            'to_account_id' => $accountB->id,
            'amount' => 200.00,
            'currency' => 'BRL',
            'transfer_date' => now(),
            'status' => 'completed',
        ]);
        $transfer->user_id = $user->id;
        $transfer->save();

        $this->assertEquals('800.00', $accountA->fresh()->balance);
        $this->assertEquals('200.00', $accountB->fresh()->balance);

        Livewire::actingAs($user)->test(Transfers::class)
            ->call('delete', $transfer->id);

        $this->assertEquals('1000.00', $accountA->fresh()->balance);
        $this->assertEquals('0.00', $accountB->fresh()->balance);
    }

    public function test_user_can_edit_transfer_and_recalculate_balances(): void
    {
        $user = User::factory()->create();
        $accountA = $this->createAccount($user, 'Conta A');
        $accountB = $this->createAccount($user, 'Conta B');

        $transaction = new Transaction([
            'account_id' => $accountA->id,
            'type' => 'income',
            'amount' => 1000.00,
            'transaction_date' => now(),
            'status' => 'posted',
        ]);
        $transaction->user_id = $user->id;
        $transaction->save();

        $transfer = new Transfer([
            'from_account_id' => $accountA->id,
            'to_account_id' => $accountB->id,
            'amount' => 200.00,
            'currency' => 'BRL',
            'transfer_date' => now(),
            'status' => 'completed',
        ]);
        $transfer->user_id = $user->id;
        $transfer->save();

        $this->assertEquals('800.00', $accountA->fresh()->balance);
        $this->assertEquals('200.00', $accountB->fresh()->balance);

        Livewire::actingAs($user)->test(Transfers::class)
            ->call('edit', $transfer->id)
            ->set('amount', '300.00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('700.00', $accountA->fresh()->balance);
        $this->assertEquals('300.00', $accountB->fresh()->balance);

        // Confirm only 1 transfer exists
        $this->assertEquals(1, Transfer::count());
        
        // Confirm no artificial transaction was created
        $this->assertEquals(1, Transaction::count());
    }

    public function test_cannot_edit_another_users_transfer(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $accountA = $this->createAccount($owner, 'Conta A');
        $accountB = $this->createAccount($owner, 'Conta B');

        $transfer = new Transfer([
            'from_account_id' => $accountA->id,
            'to_account_id' => $accountB->id,
            'amount' => 200.00,
            'currency' => 'BRL',
            'transfer_date' => now(),
            'status' => 'completed',
        ]);
        $transfer->user_id = $owner->id;
        $transfer->save();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($intruder)->test(Transfers::class)
            ->call('edit', $transfer->id);
    }
}
