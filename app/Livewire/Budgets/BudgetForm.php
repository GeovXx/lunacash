<?php

namespace App\Livewire\Budgets;

use App\Models\Budget;
use App\Services\BudgetService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BudgetForm extends Component
{
    public $budgetId;

    public $name;

    public $period_start;

    public $period_end;

    public $target_amount;

    public $isOpen = false;

    protected $listeners = ['openBudgetForm'];

    protected $rules = [
        'name' => 'required|string|max:255',
        'period_start' => 'required|date',
        'period_end' => 'required|date|after_or_equal:period_start',
        'target_amount' => 'required|numeric|min:0',
    ];

    public function openBudgetForm($id = null)
    {
        $this->resetValidation();
        $this->reset(['budgetId', 'name', 'period_start', 'period_end', 'target_amount']);

        if ($id) {
            $budget = Budget::where('user_id', Auth::id())->findOrFail($id);
            $this->budgetId = $budget->id;
            $this->name = $budget->name;
            $this->period_start = $budget->period_start->format('Y-m-d');
            $this->period_end = $budget->period_end->format('Y-m-d');
            $this->target_amount = $budget->target_amount;
        } else {
            $this->period_start = now()->startOfMonth()->format('Y-m-d');
            $this->period_end = now()->endOfMonth()->format('Y-m-d');
            $this->target_amount = 0;
        }

        $this->isOpen = true;
    }

    public function save()
    {
        $this->validate();

        $service = new BudgetService;
        $data = [
            'user_id' => Auth::id(),
            'name' => $this->name,
            'period_start' => $this->period_start,
            'period_end' => $this->period_end,
            'target_amount' => $this->target_amount,
        ];

        if ($this->budgetId) {
            $budget = Budget::where('user_id', Auth::id())->findOrFail($this->budgetId);
            $service->updateBudget($budget, $data);
        } else {
            $service->createBudget($data);
        }

        $this->isOpen = false;
        $this->dispatch('budgetSaved');
    }

    public function render()
    {
        return view('livewire.budgets.budget-form');
    }
}
