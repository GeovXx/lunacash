<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Category;
use App\Services\InstallmentPlanService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class InstallmentPlanForm extends Component
{
    public $title;

    public $account_id;

    public $category_id;

    public $direction = 'payable';

    public $total_amount;

    public $installments_count = 1;

    public $first_due_date;

    public $frequency = 'monthly';

    public $notes;

    public $isModalOpen = false;

    protected function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'account_id' => 'required|uuid|exists:accounts,id',
            'category_id' => 'required|uuid|exists:categories,id',
            'direction' => 'required|in:payable,receivable',
            'total_amount' => 'required|numeric|min:0.01',
            'installments_count' => 'required|integer|min:1',
            'first_due_date' => 'required|date',
            'frequency' => 'required|in:weekly,biweekly,monthly',
            'notes' => 'nullable|string',
        ];
    }

    public function mount()
    {
        $this->first_due_date = today()->toDateString();
    }

    public function openModal()
    {
        $this->reset(['title', 'account_id', 'category_id', 'direction', 'total_amount', 'installments_count', 'notes', 'frequency']);
        $this->first_due_date = today()->toDateString();
        $this->direction = 'payable';
        $this->frequency = 'monthly';
        $this->installments_count = 1;
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function save(InstallmentPlanService $service)
    {
        $this->validate();

        // Extra authorization checks for account and category ownership
        $account = Account::where('user_id', Auth::id())->findOrFail($this->account_id);

        $category = Category::where('id', $this->category_id)
            ->where(function ($q) {
                $q->where('user_id', Auth::id())->orWhereNull('user_id');
            })->firstOrFail();

        if ($this->direction === 'payable' && $category->type !== 'expense') {
            $this->addError('category_id', 'Payable plans must use an expense category.');

            return;
        }

        if ($this->direction === 'receivable' && $category->type !== 'income') {
            $this->addError('category_id', 'Receivable plans must use an income category.');

            return;
        }

        try {
            $service->createPlanWithObligations([
                'user_id' => Auth::id(),
                'account_id' => $this->account_id,
                'category_id' => $this->category_id,
                'direction' => $this->direction,
                'title' => $this->title,
                'total_amount' => $this->total_amount,
                'installments_count' => $this->installments_count,
                'first_due_date' => $this->first_due_date,
                'frequency' => $this->frequency,
                'notes' => $this->notes,
            ]);

            $this->closeModal();
            $this->dispatch('installment-plan-created');
        } catch (\Exception $e) {
            $this->addError('general', 'Erro ao criar plano: '.$e->getMessage());
        }
    }

    public function render()
    {
        $accounts = Account::where('user_id', Auth::id())->where('status', 'active')->get();

        $categories = Category::where(function ($q) {
            $q->where('user_id', Auth::id())->orWhereNull('user_id');
        })
            ->where('type', $this->direction === 'payable' ? 'expense' : 'income')
            ->orderBy('name')
            ->get();

        return view('livewire.installment-plan-form', [
            'accounts' => $accounts,
            'categories' => $categories,
        ]);
    }
}
