<?php

namespace Tests\Feature;

use App\Livewire\Goals\ContributionForm;
use App\Livewire\Goals\GoalForm;
use App\Livewire\Goals\GoalsList;
use App\Livewire\Goals\GoalDetail;
use App\Models\Account;
use App\Models\FinancialGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GoalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_goals_list()
    {
        $user = User::factory()->create();

        $goal = new FinancialGoal([
            'name' => 'Comprar Carro',
            'target_amount' => '50000.00',
            'target_date' => '2026-12-31',
            'status' => 'active',
            'currency' => 'BRL',
        ]);
        $goal->forceFill(['user_id' => $user->id, 'current_amount' => '0.00'])->save();

        Livewire::actingAs($user)
            ->test(GoalsList::class)
            ->assertSee('Comprar Carro');
    }

    public function test_user_can_create_goal()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(GoalForm::class)
            ->set('name', 'Viagem')
            ->set('target_amount', '10000.00')
            ->set('target_date', '2026-10-10')
            ->set('status', 'active')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('financial_goals', [
            'user_id' => $user->id,
            'name' => 'Viagem',
            'target_amount' => '10000.00',
        ]);
    }

    public function test_user_can_edit_their_own_goal()
    {
        $user = User::factory()->create();

        $goal = new FinancialGoal([
            'name' => 'Comprar Moto',
            'target_amount' => '15000.00',
            'target_date' => '2026-12-31',
            'status' => 'active',
            'currency' => 'BRL',
        ]);
        $goal->forceFill(['user_id' => $user->id, 'current_amount' => '0.00'])->save();

        Livewire::actingAs($user)
            ->test(GoalForm::class, ['goal' => $goal])
            ->set('name', 'Moto Nova')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('financial_goals', [
            'id' => $goal->id,
            'name' => 'Moto Nova',
        ]);
    }

    public function test_user_cannot_edit_another_users_goal()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $goalA = new FinancialGoal([
            'name' => 'Comprar Moto',
            'target_amount' => '15000.00',
            'target_date' => '2026-12-31',
            'status' => 'active',
            'currency' => 'BRL',
        ]);
        $goalA->forceFill(['user_id' => $userA->id, 'current_amount' => '0.00'])->save();

        Livewire::actingAs($userB)
            ->test(GoalForm::class, ['goal' => $goalA])
            ->assertStatus(404);
    }

    public function test_user_can_contribute_to_goal()
    {
        $user = User::factory()->create();

        $account = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $user->id])->save());
        
        tap(\App\Models\Transaction::factory()->make([
            'account_id' => $account->id,
            'amount' => '5000.00',
            'type' => 'income',
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $goal = new FinancialGoal([
            'name' => 'Comprar Moto',
            'target_amount' => '15000.00',
            'target_date' => '2026-12-31',
            'status' => 'active',
            'currency' => 'BRL',
        ]);
        $goal->forceFill(['user_id' => $user->id, 'current_amount' => '0.00'])->save();

        Livewire::actingAs($user)
            ->test(ContributionForm::class, ['goal' => $goal])
            ->set('account_id', $account->id)
            ->set('amount', '1000.00')
            ->set('contribution_date', '2026-08-20')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('goal_contributions', [
            'user_id' => $user->id,
            'financial_goal_id' => $goal->id,
            'amount' => '1000.00',
        ]);

        $this->assertDatabaseHas('financial_goals', [
            'id' => $goal->id,
            'current_amount' => '1000.00',
        ]);
    }

    public function test_user_cannot_contribute_more_than_account_balance()
    {
        $user = User::factory()->create();

        $account = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        tap(\App\Models\Transaction::factory()->make([
            'account_id' => $account->id,
            'amount' => '500.00',
            'type' => 'income',
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $goal = new FinancialGoal([
            'name' => 'Comprar Moto',
            'target_amount' => '15000.00',
            'target_date' => '2026-12-31',
            'status' => 'active',
            'currency' => 'BRL',
        ]);
        $goal->forceFill(['user_id' => $user->id, 'current_amount' => '0.00'])->save();

        Livewire::actingAs($user)
            ->test(ContributionForm::class, ['goal' => $goal])
            ->set('account_id', $account->id)
            ->set('amount', '1000.00')
            ->set('contribution_date', '2026-08-20')
            ->call('save')
            ->assertHasErrors('amount');
    }
}
