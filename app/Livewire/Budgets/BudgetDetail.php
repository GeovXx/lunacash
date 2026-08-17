<?php

namespace App\Livewire\Budgets;

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Services\BudgetService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BudgetDetail extends Component
{
    public $budget;

    public $progress;

    protected $listeners = ['budgetLineSaved' => 'loadBudget', 'budgetLineDeleted' => 'loadBudget'];

    public function mount($budgetId)
    {
        $this->budget = Budget::where('user_id', Auth::id())->findOrFail($budgetId);
        $this->loadBudget();
    }

    public function loadBudget()
    {
        $service = new BudgetService;
        $this->progress = $service->getBudgetProgress($this->budget);
    }

    public function deleteBudgetLine($id)
    {
        $line = BudgetLine::where('user_id', Auth::id())->findOrFail($id);
        $service = new BudgetService;
        $service->removeBudgetLine($line, Auth::id());
        $this->loadBudget();
    }

    public function render()
    {
        return view('livewire.budgets.budget-detail')->layout('layouts.app');
    }
}
