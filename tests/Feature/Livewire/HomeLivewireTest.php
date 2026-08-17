<?php

namespace Tests\Feature\Livewire;

use App\Http\Livewire\Home;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomeLivewireTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_renders_home_dashboard_successfully()
    {
        Livewire::actingAs($this->user)
            ->test(Home::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.home')
            ->assertSee('Visão Geral')
            ->assertSee('Saldo em Contas')
            ->assertSee('Receitas (Mês)')
            ->assertSee('Despesas (Mês)')
            ->assertSee('Faturas Pendentes')
            ->assertSee('Minhas Contas')
            ->assertSee('Orçamentos Ativos')
            ->assertSee('Metas Financeiras')
            ->assertSee('Próximos 15 dias')
            ->assertSee('Fluxo de Caixa')
            ->assertSee('Gastos por Categoria');
    }

    public function test_dashboard_does_not_mutate_data()
    {
        // Simple rendering test ensures we just read
        $initialCount = Transaction::count();

        Livewire::actingAs($this->user)
            ->test(Home::class)
            ->assertStatus(200);

        $this->assertEquals($initialCount, Transaction::count());
    }
}
