<?php

namespace Tests\Feature;

use App\Livewire\CreditCards;
use App\Models\CreditCard;
use App\Models\CreditCardTransaction;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreditCardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_credit_card()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreditCards::class)
            ->set('name', 'Nubank')
            ->set('issuer', 'Mastercard')
            ->set('last_digits', '1234')
            ->set('limit_amount', '5000.00')
            ->set('statement_day', 10)
            ->set('due_day', 20)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('credit_cards', [
            'user_id' => $user->id,
            'name' => 'Nubank',
            'limit_amount' => '5000.00',
            'available_limit' => '5000.00',
        ]);
    }

    public function test_user_can_edit_their_own_credit_card()
    {
        $user = User::factory()->create();
        $card = tap(CreditCard::factory()->make([
            'limit_amount' => '1000.00',
            'available_limit' => '1000.00',
            'name' => 'Old Name',
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        Livewire::actingAs($user)
            ->test(CreditCards::class)
            ->call('edit', $card->id)
            ->set('name', 'New Name')
            ->set('limit_amount', '2000.00')
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('credit_cards', [
            'id' => $card->id,
            'name' => 'New Name',
            'limit_amount' => '2000.00',
            'available_limit' => '2000.00',
        ]);
    }

    public function test_cannot_edit_credit_card_if_new_limit_is_lower_than_used_limit()
    {
        $user = User::factory()->create();
        $card = tap(CreditCard::factory()->make([
            'limit_amount' => '1000.00',
            'available_limit' => '200.00', // 800 used
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        Livewire::actingAs($user)
            ->test(CreditCards::class)
            ->call('edit', $card->id)
            ->set('limit_amount', '500.00') // 500 is less than 800
            ->call('store')
            ->assertHasErrors(['limit_amount']);

        $this->assertDatabaseHas('credit_cards', [
            'id' => $card->id,
            'limit_amount' => '1000.00',
        ]);
    }

    public function test_user_cannot_edit_another_users_credit_card()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        
        $cardA = tap(CreditCard::factory()->make(), fn($m) => $m->forceFill(['user_id' => $userA->id])->save());

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($userB)
            ->test(CreditCards::class)
            ->call('edit', $cardA->id);
    }

    public function test_user_can_delete_their_own_credit_card()
    {
        $user = User::factory()->create();
        $card = tap(CreditCard::factory()->make(), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        Livewire::actingAs($user)
            ->test(CreditCards::class)
            ->call('confirmDelete', $card->id)
            ->call('delete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('credit_cards', [
            'id' => $card->id,
        ]);
    }

    public function test_user_cannot_delete_credit_card_with_transactions()
    {
        $user = User::factory()->create();
        $card = tap(CreditCard::factory()->make(), fn($m) => $m->forceFill(['user_id' => $user->id])->save());
        $category = tap(Category::factory()->make(['type' => 'expense']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $transaction = new CreditCardTransaction([
            'credit_card_id' => $card->id,
            'category_id' => $category->id,
            'description' => 'Test',
            'amount' => 100,
            'currency' => 'BRL',
            'transaction_date' => now()->toDateString(),
            'status' => 'posted',
            'installments_total' => 1,
        ]);
        $transaction->user_id = $user->id;
        $transaction->save();

        Livewire::actingAs($user)
            ->test(CreditCards::class)
            ->call('confirmDelete', $card->id)
            ->call('delete')
            ->assertHasNoErrors(); // No validation error, but uses flash message

        // Should still exist
        $this->assertDatabaseHas('credit_cards', [
            'id' => $card->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_credit_card()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        
        $cardA = tap(CreditCard::factory()->make(), fn($m) => $m->forceFill(['user_id' => $userA->id])->save());

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($userB)
            ->test(CreditCards::class)
            ->call('confirmDelete', $cardA->id);
    }
}
