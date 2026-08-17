<?php

namespace Tests\Feature;

use App\Livewire\CreditCardTransactions;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\CreditCardInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreditCardTransactionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_credit_card_transaction_in_cash()
    {
        $user = User::factory()->create();
        
        $card = tap(CreditCard::factory()->make([
            'limit_amount' => '5000.00',
            'available_limit' => '5000.00',
            'statement_day' => 10,
            'due_day' => 20,
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $category = tap(Category::factory()->make([
            'type' => 'expense'
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        Livewire::actingAs($user)
            ->test(CreditCardTransactions::class)
            ->set('creditCardId', $card->id)
            ->set('categoryId', $category->id)
            ->set('description', 'Test Purchase Cash')
            ->set('amount', '100.00')
            ->set('transactionDate', '2026-08-15')
            ->set('installmentsTotal', 1)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('credit_card_transactions', [
            'user_id' => $user->id,
            'credit_card_id' => $card->id,
            'amount' => '100.00',
            'installments_total' => 1,
        ]);

        $this->assertDatabaseHas('credit_cards', [
            'id' => $card->id,
            'available_limit' => '4900.00',
        ]);
        
        $this->assertDatabaseHas('credit_card_installments', [
            'user_id' => $user->id,
            'amount' => '100.00',
            'sequence' => 1,
        ]);
    }

    public function test_user_can_create_credit_card_transaction_in_3_installments_in_different_invoices()
    {
        $user = User::factory()->create();
        
        $card = tap(CreditCard::factory()->make([
            'limit_amount' => '5000.00',
            'available_limit' => '5000.00',
            'statement_day' => 10,
            'due_day' => 20,
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $category = tap(Category::factory()->make([
            'type' => 'expense'
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        Livewire::actingAs($user)
            ->test(CreditCardTransactions::class)
            ->set('creditCardId', $card->id)
            ->set('categoryId', $category->id)
            ->set('description', 'Test Purchase 3x')
            ->set('amount', '300.00')
            ->set('transactionDate', '2026-08-15')
            ->set('installmentsTotal', 3)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('credit_card_transactions', [
            'user_id' => $user->id,
            'amount' => '300.00',
            'installments_total' => 3,
        ]);

        $this->assertDatabaseHas('credit_cards', [
            'id' => $card->id,
            'available_limit' => '4700.00',
        ]);
        
        // 3 installments
        $this->assertEquals(3, \App\Models\CreditCardInstallment::where('user_id', $user->id)->count());
        
        // 3 different invoices
        $invoiceIds = \App\Models\CreditCardInstallment::where('user_id', $user->id)->pluck('invoice_id')->unique();
        $this->assertCount(3, $invoiceIds);
    }

    public function test_cannot_create_transaction_if_limit_exceeded()
    {
        $user = User::factory()->create();
        
        $card = tap(CreditCard::factory()->make([
            'limit_amount' => '5000.00',
            'available_limit' => '50.00',
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $category = tap(Category::factory()->make([
            'type' => 'expense'
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        Livewire::actingAs($user)
            ->test(CreditCardTransactions::class)
            ->set('creditCardId', $card->id)
            ->set('categoryId', $category->id)
            ->set('description', 'Too expensive')
            ->set('amount', '100.00')
            ->set('transactionDate', '2026-08-15')
            ->set('installmentsTotal', 1)
            ->call('store')
            ->assertHasErrors(['amount']);

        $this->assertDatabaseMissing('credit_card_transactions', [
            'description' => 'Too expensive',
        ]);
    }

    public function test_cannot_create_transaction_with_income_category()
    {
        $user = User::factory()->create();
        
        $card = tap(CreditCard::factory()->make(), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $category = tap(Category::factory()->make([
            'type' => 'income'
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        Livewire::actingAs($user)
            ->test(CreditCardTransactions::class)
            ->set('creditCardId', $card->id)
            ->set('categoryId', $category->id)
            ->set('description', 'Test Purchase')
            ->set('amount', '100.00')
            ->set('transactionDate', '2026-08-15')
            ->set('installmentsTotal', 1)
            ->call('store')
            ->assertHasErrors(['categoryId']);
    }

    public function test_cannot_create_transaction_with_another_users_category()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        
        $cardA = tap(CreditCard::factory()->make(), fn($m) => $m->forceFill(['user_id' => $userA->id])->save());

        $categoryB = tap(Category::factory()->make([
            'type' => 'expense'
        ]), fn($m) => $m->forceFill(['user_id' => $userB->id])->save());

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($userA)
            ->test(CreditCardTransactions::class)
            ->set('creditCardId', $cardA->id)
            ->set('categoryId', $categoryB->id)
            ->set('description', 'Test Purchase')
            ->set('amount', '100.00')
            ->set('transactionDate', '2026-08-15')
            ->set('installmentsTotal', 1)
            ->call('store');
    }

    public function test_user_cannot_use_another_users_credit_card()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        
        $cardB = tap(CreditCard::factory()->make(), fn($m) => $m->forceFill(['user_id' => $userB->id])->save());
        $categoryA = tap(Category::factory()->make(['type' => 'expense']), fn($m) => $m->forceFill(['user_id' => $userA->id])->save());

        Livewire::actingAs($userA)
            ->test(CreditCardTransactions::class)
            ->set('creditCardId', $cardB->id)
            ->set('categoryId', $categoryA->id)
            ->set('description', 'Test Purchase')
            ->set('amount', '100.00')
            ->set('transactionDate', '2026-08-15')
            ->set('installmentsTotal', 1)
            ->call('store')
            ->assertHasErrors(['creditCardId']);
    }
}
