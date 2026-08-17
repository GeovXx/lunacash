<?php

namespace Tests\Feature;

use App\Http\Livewire\GlobalSearch;
use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_does_not_search_if_term_is_less_than_3_characters()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(GlobalSearch::class)
            ->set('search', 'ab')
            ->assertSet('results.accounts', [])
            ->assertSet('results.categories', [])
            ->assertSet('results.credit_cards', [])
            ->assertSet('results.transactions', []);
    }

    public function test_it_searches_accounts_categories_cards_and_transactions()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Own records
        $account = tap(Account::factory()->make(['name' => 'Nubank Conta']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());
        $category = tap(Category::factory()->make(['name' => 'Nubank Fatura', 'type' => 'expense']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());
        $card = tap(CreditCard::factory()->make(['name' => 'Nubank Cartão']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());
        $transaction = tap(Transaction::factory()->make([
            'account_id' => $account->id,
            'description' => 'Pagamento Nubank',
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        // Other user records (should not be found)
        tap(Account::factory()->make(['name' => 'Nubank Conta Fake']), fn($m) => $m->forceFill(['user_id' => $otherUser->id])->save());
        tap(Category::factory()->make(['name' => 'Nubank Fatura Fake', 'type' => 'expense']), fn($m) => $m->forceFill(['user_id' => $otherUser->id])->save());
        tap(CreditCard::factory()->make(['name' => 'Nubank Cartão Fake']), fn($m) => $m->forceFill(['user_id' => $otherUser->id])->save());
        
        $otherAccount = tap(Account::factory()->make(['name' => 'Outra']), fn($m) => $m->forceFill(['user_id' => $otherUser->id])->save());
        tap(Transaction::factory()->make([
            'account_id' => $otherAccount->id,
            'description' => 'Pagamento Nubank Fake',
        ]), fn($m) => $m->forceFill(['user_id' => $otherUser->id])->save());

        $component = Livewire::actingAs($user)
            ->test(GlobalSearch::class)
            ->set('search', 'Nubank');

        $results = $component->get('results');

        $this->assertCount(1, $results['accounts']);
        $this->assertEquals('Nubank Conta', $results['accounts'][0]['name']);

        $this->assertCount(1, $results['categories']);
        $this->assertEquals('Nubank Fatura', $results['categories'][0]['name']);

        $this->assertCount(1, $results['credit_cards']);
        $this->assertEquals('Nubank Cartão', $results['credit_cards'][0]['name']);

        $this->assertCount(1, $results['transactions']);
        $this->assertEquals('Pagamento Nubank', $results['transactions'][0]['description']);
    }

    public function test_clear_method_resets_search_and_results()
    {
        $user = User::factory()->create();

        $account = tap(Account::factory()->make(['name' => 'Nubank Conta']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $component = Livewire::actingAs($user)
            ->test(GlobalSearch::class)
            ->set('search', 'Nubank');
            
        $results = $component->get('results');
        $this->assertCount(1, $results['accounts']);

        $component->call('clear')
            ->assertSet('search', '')
            ->assertSet('results.accounts', [])
            ->assertSet('results.categories', [])
            ->assertSet('results.credit_cards', [])
            ->assertSet('results.transactions', []);
    }
}
