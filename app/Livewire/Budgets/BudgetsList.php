<?php

namespace App\Livewire\Budgets;

use App\Models\Budget;
use App\Services\BudgetService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BudgetsList extends Component
{
    public $budgets = [];

    protected $listeners = ['budgetSaved' => 'loadBudgets', 'budgetDeleted' => 'loadBudgets'];

    public function mount()
    {
        $this->loadBudgets();
    }

    public function loadBudgets()
    {
        $service = new BudgetService;
        $userBudgets = Budget::where('user_id', Auth::id())
            ->orderBy('period_start', 'desc')
            ->get();

        $this->budgets = $userBudgets->map(function ($budget) use ($service) {
            $progress = $service->getBudgetProgress($budget);
            $budget->progress = $progress;

            return $budget;
        });
    }

    public function deleteBudget($id)
    {
        $budget = Budget::where('user_id', Auth::id())->findOrFail($id);
        $budget->delete();
        $this->loadBudgets();
    }

    public function render()
    {
        return view('livewire.budgets.budgets-list');
    }
}
