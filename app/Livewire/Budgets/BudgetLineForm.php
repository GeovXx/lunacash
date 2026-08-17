<?php

namespace App\Livewire\Budgets;

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\Category;
use App\Services\BudgetService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BudgetLineForm extends Component
{
    public $budget_id;

    public $lineId;

    public $category_id;

    public $planned_amount;

    public $isOpen = false;

    protected $listeners = ['openBudgetLineForm'];

    protected $rules = [
        'category_id' => 'required|uuid',
        'planned_amount' => 'required|numeric|min:0.01',
    ];

    public function mount($budgetId)
    {
        $this->budget_id = $budgetId;
    }

    public function openBudgetLineForm($id = null)
    {
        $this->resetValidation();
        $this->reset(['lineId', 'category_id', 'planned_amount']);

        if ($id) {
            $line = BudgetLine::where('user_id', Auth::id())->where('budget_id', $this->budget_id)->findOrFail($id);
            $this->lineId = $line->id;
            $this->category_id = $line->category_id;
            $this->planned_amount = $line->planned_amount;
        }

        $this->isOpen = true;
    }

    public function getExpenseCategoriesProperty()
    {
        return Category::visibleTo(Auth::user())
            ->where('type', 'expense')
            ->orderBy('name')
            ->get();
    }

    public function save()
    {
        $this->validate();

        $service = new BudgetService;
        $budget = Budget::where('user_id', Auth::id())->findOrFail($this->budget_id);

        $data = [
            'user_id' => Auth::id(),
            'category_id' => $this->category_id,
            'planned_amount' => $this->planned_amount,
        ];

        try {
            if ($this->lineId) {
                $line = BudgetLine::where('user_id', Auth::id())->where('budget_id', $this->budget_id)->findOrFail($this->lineId);
                $service->updateBudgetLine($line, $data);
            } else {
                $service->addBudgetLine($budget, $data);
            }

            $this->isOpen = false;
            $this->dispatch('budgetLineSaved');
        } catch (\Exception $e) {
            $this->addError('general', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.budgets.budget-line-form', [
            'expenseCategories' => $this->expenseCategories,
        ]);
    }
}
