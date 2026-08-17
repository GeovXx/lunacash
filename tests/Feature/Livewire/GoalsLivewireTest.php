<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Goals\ContributionForm;
use App\Livewire\Goals\GoalDetail;
use App\Livewire\Goals\GoalForm;
use App\Livewire\Goals\GoalsList;
use App\Models\FinancialGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GoalsLivewireTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    private FinancialGoal $goal;

    private FinancialGoal $otherGoal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->goal = new FinancialGoal([
            'name' => 'Vacation',
            'target_amount' => '5000.00',
            'current_amount' => 0,
            'status' => 'active',
        ]);
        $this->goal->user_id = $this->user->id;
        $this->goal->save();

        $this->otherGoal = new FinancialGoal([
            'name' => 'Other Vacation',
            'target_amount' => '5000.00',
            'current_amount' => 0,
            'status' => 'active',
        ]);
        $this->otherGoal->user_id = $this->otherUser->id;
        $this->otherGoal->save();
    }

    public function test_goals_list_renders_and_isolates()
    {
        Livewire::actingAs($this->user)
            ->test(GoalsList::class)
            ->assertStatus(200)
            ->assertSee('Vacation')
            ->assertDontSee('Other Vacation');
    }

    public function test_goal_detail_renders()
    {
        Livewire::actingAs($this->user)
            ->test(GoalDetail::class, ['goal' => $this->goal])
            ->assertStatus(200)
            ->assertSee('Vacation');
    }

    public function test_goal_detail_isolates_users()
    {
        Livewire::actingAs($this->user)
            ->test(GoalDetail::class, ['goal' => $this->otherGoal])
            ->assertNotFound();
    }

    public function test_goal_form_isolates_users()
    {
        Livewire::actingAs($this->user)
            ->test(GoalForm::class, ['goal' => $this->otherGoal])
            ->assertNotFound();
    }

    public function test_contribution_form_isolates_users()
    {
        Livewire::actingAs($this->user)
            ->test(ContributionForm::class, ['goal' => $this->otherGoal])
            ->assertNotFound();
    }
}
