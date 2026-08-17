<?php

namespace App\Livewire\Goals;

use App\Models\FinancialGoal;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class GoalDetail extends Component
{
    public FinancialGoal $goal;

    public function mount(FinancialGoal $goal)
    {
        $this->authorizeAccess($goal);
        $this->goal = $goal;
    }

    private function authorizeAccess(FinancialGoal $goal)
    {
        if ($goal->user_id !== auth()->id()) {
            abort(404);
        }
    }

    #[On('goal-saved')]
    #[On('contribution-added')]
    public function refreshGoal()
    {
        $this->goal->refresh();
    }

    public function render()
    {
        $contributions = $this->goal->contributions()
            ->with('account')
            ->orderBy('contribution_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('livewire.goals.goal-detail', [
            'contributions' => $contributions,
        ]);
    }
}
