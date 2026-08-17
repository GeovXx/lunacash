<?php

namespace App\Livewire\Goals;

use App\Models\FinancialGoal;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class GoalsList extends Component
{
    public function getGoalsProperty()
    {
        return FinancialGoal::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[On('goal-saved')]
    #[On('contribution-added')]
    public function refreshGoals()
    {
        // Re-render
    }

    public function render()
    {
        return view('livewire.goals.goals-list');
    }
}
