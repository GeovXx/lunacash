<?php

namespace Tests\Feature\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\CreditCardInvoice;
use App\Models\CreditCardTransaction;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    public function test_cash_flow_export_security_and_format()
    {
        $account = tap(Account::factory()->make(['name' => 'Conta Teste'])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        $category = tap(Category::factory()->make(['name' => 'Categoria Teste'])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        // User transaction
        tap(Transaction::factory()->make(['account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'income',
            'amount' => 1500.50,
            'transaction_date' => Carbon::now()->startOfMonth(),
            'description' => 'Salário',])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $otherAccount = tap(Account::factory()->make(['name' => 'Conta Outro'])->forceFill(['user_id' => $this->otherUser->id]), fn($m) => $m->save());
        $otherCategory = tap(Category::factory()->make(['name' => 'Categoria Outro'])->forceFill(['user_id' => $this->otherUser->id]), fn($m) => $m->save());

        // Other user transaction
        tap(Transaction::factory()->make(['account_id' => $otherAccount->id,
            'category_id' => $otherCategory->id,
            'type' => 'income',
            'amount' => 1500.50,
            'transaction_date' => Carbon::now()->startOfMonth(),
            'description' => 'Salário Outro',])->forceFill(['user_id' => $this->otherUser->id]), fn($m) => $m->save());

        $response = $this->actingAs($this->user)->get(route('export.cash-flow', [
            'start' => Carbon::now()->startOfMonth()->toDateString(),
            'end' => Carbon::now()->endOfMonth()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        
        $content = $response->streamedContent();
        
        // Assert BOM exists
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        
        // Assert headers
        $this->assertStringContainsString('Data;Referência/Descrição;Categoria;Conta;Tipo;"Valor (R$)"', $content);
        
        // Assert user's transaction exists with correct format
        $this->assertStringContainsString('Salário;"Categoria Teste";"Conta Teste";Receita;1500,50', $content);
        
        // Assert other user's transaction DOES NOT exist
        $this->assertStringNotContainsString('Salário Outro', $content);
        $this->assertStringNotContainsString('5000,00', $content);
    }

    public function test_categories_export()
    {
        $category1 = tap(Category::factory()->make(['name' => 'Alimentação'])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        $category2 = tap(Category::factory()->make(['name' => 'Transporte'])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $account = tap(Account::factory()->make()->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        tap(Transaction::factory()->make(['account_id' => $account->id,
            'category_id' => $category1->id,
            'type' => 'expense',
            'amount' => 100.25,
            'transaction_date' => Carbon::now(),])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $card = tap(CreditCard::factory()->make()->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        tap(CreditCardTransaction::factory()->make(['credit_card_id' => $card->id,
            'category_id' => $category2->id,
            'amount' => 50.75,
            'transaction_date' => Carbon::now(),])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $response = $this->actingAs($this->user)->get(route('export.categories', [
            'start' => Carbon::now()->startOfMonth()->toDateString(),
            'end' => Carbon::now()->endOfMonth()->toDateString(),
        ]));

        $response->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString('Categoria;"Valor Consumido (R$)"', $content);
        $this->assertStringContainsString('Alimentação;100,25', $content);
        $this->assertStringContainsString('Transporte;50,75', $content);
        $this->assertStringContainsString('"Total Geral";151,00', $content);
    }

    public function test_balances_export()
    {
        $account = tap(Account::factory()->make(['name' => 'Banco Itaú',
            'status' => 'active'])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        tap(Transaction::factory()->make(['account_id' => $account->id,
            'type' => 'income',
            'amount' => 1250.60,
            'transaction_date' => Carbon::now(),])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $card = tap(CreditCard::factory()->make(['name' => 'Nubank'])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
        tap(CreditCardInvoice::factory()->make(['credit_card_id' => $card->id,
            'due_date' => Carbon::now()->addDays(5),
            'total_amount' => 500.00,
            'paid_amount' => 0.00,
            'status' => 'open'])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        $response = $this->actingAs($this->user)->get(route('export.balances'));

        $response->assertStatus(200);
        $content = $response->streamedContent();

        // Check accounts
        $this->assertStringContainsString('"Banco Itaú";1250,60', $content);
        
        // Check invoices
        $this->assertStringContainsString('Nubank;' . Carbon::now()->addDays(5)->format('d/m/Y') . ';500,00', $content);
        
        // Check Net worth (1250.60 - 500 = 750.60)
        $this->assertStringContainsString('"Patrimônio Líquido";750,60', $content);
    }
}
