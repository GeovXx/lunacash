<?php

namespace Tests\Feature;

use App\Livewire\Budgets\BudgetForm;
use App\Livewire\Budgets\BudgetLineForm;
use App\Livewire\Budgets\BudgetsList;
use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BudgetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_budgets_list()
    {
        $user = User::factory()->create();

        $budget = new Budget([
            'name' => 'Agosto 2026',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'target_amount' => '2000.00',
            'status' => 'active',
        ]);
        $budget->forceFill(['user_id' => $user->id])->save();

        Livewire::actingAs($user)
            ->test(BudgetsList::class)
            ->assertSee('Agosto 2026');
    }

    public function test_user_can_create_budget()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(BudgetForm::class)
            ->call('openBudgetForm')
            ->set('name', 'Setembro 2026')
            ->set('period_start', '2026-09-01')
            ->set('period_end', '2026-09-30')
            ->set('target_amount', '2500.00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'name' => 'Setembro 2026',
            'target_amount' => '2500.00',
        ]);
    }

    public function test_user_can_edit_their_own_budget()
    {
        $user = User::factory()->create();

        $budget = new Budget([
            'name' => 'Agosto 2026',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'target_amount' => '2000.00',
            'status' => 'active',
        ]);
        $budget->forceFill(['user_id' => $user->id])->save();

        Livewire::actingAs($user)
            ->test(BudgetForm::class)
            ->call('openBudgetForm', $budget->id)
            ->set('name', 'Agosto Atualizado')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'name' => 'Agosto Atualizado',
        ]);
    }

    public function test_user_cannot_edit_another_users_budget()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $budgetA = new Budget([
            'name' => 'Agosto 2026',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'target_amount' => '2000.00',
            'status' => 'active',
        ]);
        $budgetA->forceFill(['user_id' => $userA->id])->save();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($userB)
            ->test(BudgetForm::class)
            ->call('openBudgetForm', $budgetA->id);
    }

    public function test_user_can_add_budget_line()
    {
        $user = User::factory()->create();

        $budget = new Budget([
            'name' => 'Agosto 2026',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'target_amount' => '2000.00',
            'status' => 'active',
        ]);
        $budget->forceFill(['user_id' => $user->id])->save();

        $category = tap(Category::factory()->make(['type' => 'expense']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        Livewire::actingAs($user)
            ->test(BudgetLineForm::class, ['budgetId' => $budget->id])
            ->call('openBudgetLineForm')
            ->set('category_id', $category->id)
            ->set('planned_amount', '500.00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('budget_lines', [
            'user_id' => $user->id,
            'budget_id' => $budget->id,
            'category_id' => $category->id,
            'planned_amount' => '500.00',
        ]);
    }

    public function test_user_cannot_add_budget_line_to_another_users_budget()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $budgetA = new Budget([
            'name' => 'Agosto 2026',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'target_amount' => '2000.00',
            'status' => 'active',
        ]);
        $budgetA->forceFill(['user_id' => $userA->id])->save();

        $categoryB = tap(Category::factory()->make(['type' => 'expense']), fn($m) => $m->forceFill(['user_id' => $userB->id])->save());

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($userB)
            ->test(BudgetLineForm::class, ['budgetId' => $budgetA->id])
            ->call('openBudgetLineForm')
            ->set('category_id', $categoryB->id)
            ->set('planned_amount', '500.00')
            ->call('save');
    }
}
