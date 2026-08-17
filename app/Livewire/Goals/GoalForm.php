<?php

namespace App\Livewire\Goals;

use App\Models\FinancialGoal;
use App\Services\GoalService;
use Livewire\Component;

class GoalForm extends Component
{
    public ?FinancialGoal $goal = null;

    public $name = '';

    public $description = '';

    public $target_amount = '';

    public $target_date = '';

    public $status = 'active';

    public function mount(?FinancialGoal $goal = null)
    {
        if ($goal && $goal->exists) {
            $this->authorizeAccess($goal);
            $this->goal = $goal;
            $this->name = $goal->name;
            $this->description = $goal->description;
            $this->target_amount = $goal->target_amount;
            $this->target_date = $goal->target_date ? $goal->target_date->format('Y-m-d') : null;
            $this->status = $goal->status;
        }
    }

    private function authorizeAccess(FinancialGoal $goal)
    {
        if ($goal->user_id !== auth()->id()) {
            abort(404);
        }
    }

    public function save(GoalService $goalService)
    {
        $data = $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_amount' => 'required|numeric|min:0.01',
            'target_date' => 'nullable|date',
            'status' => 'required|in:active,paused,completed,cancelled',
        ]);

        if ($this->goal && $this->goal->exists) {
            try {
                $goalService->updateGoal($this->goal->id, $data, auth()->id());
            } catch (\InvalidArgumentException $e) {
                $this->addError('target_amount', $e->getMessage());

                return;
            }
        } else {
            $goalService->createGoal($data, auth()->id());
        }

        $this->dispatch('goal-saved');
        $this->dispatch('closeModal');
    }

    public function render()
    {
        return view('livewire.goals.goal-form');
    }
}
