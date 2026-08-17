<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Budgets\BudgetDetail;
use App\Livewire\Budgets\BudgetForm;
use App\Livewire\Budgets\BudgetLineForm;
use App\Livewire\Budgets\BudgetsList;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BudgetsLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $otherUser;
    protected $expenseCategory;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->actingAs($this->user);
        $this->expenseCategory = tap(Category::factory()->make(['type' => 'expense', 'name' => 'Exp ' . uniqid()])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());
    }

    public function test_budgets_list_is_isolated_per_user()
    {
        $budget1 = new Budget(['name' => 'My Budget', 'period_start' => '2026-01-01', 'period_end' => '2026-01-31', 'target_amount' => 1000]);
        $budget1->user_id = $this->user->id;
        $budget1->save();

        $budget2 = new Budget(['name' => 'Other Budget', 'period_start' => '2026-01-01', 'period_end' => '2026-01-31', 'target_amount' => 1000]);
        $budget2->user_id = $this->otherUser->id;
        $budget2->save();

        Livewire::actingAs($this->user)
            ->test(BudgetsList::class)
            ->assertSee('My Budget')
            ->assertDontSee('Other Budget');
    }

    public function test_budget_detail_is_isolated()
    {
        $budget = new Budget(['name' => 'Other Budget', 'period_start' => '2026-01-01', 'period_end' => '2026-01-31']);
        $budget->user_id = $this->otherUser->id;
        $budget->save();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($this->user)
            ->test(BudgetDetail::class, ['budgetId' => $budget->id]);
    }

    public function test_budget_form_creates_and_updates()
    {
        Livewire::actingAs($this->user)
            ->test(BudgetForm::class)
            ->call('openBudgetForm')
            ->set('name', 'January')
            ->set('period_start', '2026-01-01')
            ->set('period_end', '2026-01-31')
            ->set('target_amount', 2000)
            ->call('save')
            ->assertDispatched('budgetSaved');

        $this->assertDatabaseHas('budgets', [
            'user_id' => $this->user->id,
            'name' => 'January',
            'target_amount' => 2000
        ]);

        $budget = Budget::where('user_id', $this->user->id)->first();

        Livewire::actingAs($this->user)
            ->test(BudgetForm::class)
            ->call('openBudgetForm', $budget->id)
            ->set('target_amount', 3000)
            ->call('save');

        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'target_amount' => 3000
        ]);
    }

    public function test_budget_line_form_creates_and_updates()
    {
        $budget = new Budget(['name' => 'My Budget', 'period_start' => '2026-01-01', 'period_end' => '2026-01-31']);
        $budget->user_id = $this->user->id;
        $budget->save();

        Livewire::actingAs($this->user)
            ->test(BudgetLineForm::class, ['budgetId' => $budget->id])
            ->call('openBudgetLineForm')
            ->set('category_id', $this->expenseCategory->id)
            ->set('planned_amount', 500)
            ->call('save')
            ->assertDispatched('budgetLineSaved');

        $this->assertDatabaseHas('budget_lines', [
            'budget_id' => $budget->id,
            'category_id' => $this->expenseCategory->id,
            'planned_amount' => 500
        ]);
    }

    public function test_cannot_edit_other_user_budget_or_line()
    {
        $otherBudget = new Budget(['name' => 'Other Budget', 'period_start' => '2026-01-01', 'period_end' => '2026-01-31']);
        $otherBudget->user_id = $this->otherUser->id;
        $otherBudget->save();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($this->user)
            ->test(BudgetForm::class)
            ->call('openBudgetForm', $otherBudget->id);
    }

    public function test_budget_line_cannot_use_income_category()
    {
        $budget = new Budget(['name' => 'My Budget', 'period_start' => '2026-01-01', 'period_end' => '2026-01-31']);
        $budget->user_id = $this->user->id;
        $budget->save();

        $incomeCategory = tap(Category::factory()->make(['type' => 'income', 'name' => 'Inc '.uniqid()])->forceFill(['user_id' => $this->user->id]), fn($m) => $m->save());

        Livewire::actingAs($this->user)
            ->test(BudgetLineForm::class, ['budgetId' => $budget->id])
            ->call('openBudgetLineForm')
            ->set('category_id', $incomeCategory->id)
            ->set('planned_amount', 500)
            ->call('save')
            ->assertHasErrors('general'); // The service throws exception caught by the catch block
    }

    public function test_cannot_edit_other_user_budget_line()
    {
        $otherBudget = new Budget(['name' => 'Other Budget', 'period_start' => '2026-01-01', 'period_end' => '2026-01-31']);
        $otherBudget->user_id = $this->otherUser->id;
        $otherBudget->save();

        $otherLine = new BudgetLine(['budget_id' => $otherBudget->id, 'category_id' => tap(Category::factory()->make(['type'=>'expense', 'name'=>'OExp '.uniqid()])->forceFill(['user_id' => $this->otherUser->id]), fn($m) => $m->save())->id, 'planned_amount' => 500, 'name' => 'Test']);
        $otherLine->user_id = $this->otherUser->id;
        $otherLine->save();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($this->user)
            ->test(BudgetLineForm::class, ['budgetId' => $otherBudget->id])
            ->call('openBudgetLineForm', $otherLine->id);
    }

    public function test_budget_detail_can_remove_line()
    {
        $budget = new Budget(['name' => 'My Budget', 'period_start' => '2026-01-01', 'period_end' => '2026-01-31']);
        $budget->user_id = $this->user->id;
        $budget->save();

        $line = new BudgetLine(['budget_id' => $budget->id, 'category_id' => $this->expenseCategory->id, 'planned_amount' => 500, 'name' => 'Test']);
        $line->user_id = $this->user->id;
        $line->save();

        Livewire::actingAs($this->user)
            ->test(BudgetDetail::class, ['budgetId' => $budget->id])
            ->call('deleteBudgetLine', $line->id);

        $this->assertDatabaseMissing('budget_lines', ['id' => $line->id]);
    }
}
