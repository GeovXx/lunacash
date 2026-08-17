<?php

namespace App\Livewire\Payables;

use App\Models\Account;
use App\Models\Category;
use App\Models\FinancialObligation;
use App\Services\FinancialObligationService;
use Livewire\Component;

class PayableForm extends Component
{
    public $isOpen = false;

    public $obligationId = null;

    public $title;

    public $amount;

    public $due_date;

    public $account_id;

    public $category_id;

    public $notes;

    public $isInstallment = false;

    public $isPaidOrCancelled = false;

    protected $listeners = ['openPayableForm'];

    public function openPayableForm($data = null)
    {
        $this->resetValidation();
        $this->reset(['obligationId', 'title', 'amount', 'due_date', 'account_id', 'category_id', 'notes', 'isInstallment', 'isPaidOrCancelled']);

        if (isset($data['obligationId'])) {
            $this->obligationId = $data['obligationId'];
            $this->loadObligation();
        } else {
            // Defaults for new standalone
            $this->due_date = now()->toDateString();
        }

        $this->isOpen = true;
    }

    public function loadObligation()
    {
        $obligation = FinancialObligation::where('id', $this->obligationId)
            ->where('user_id', auth()->id())
            ->where('direction', 'payable')
            ->firstOrFail();

        $this->title = $obligation->title;
        $this->amount = $obligation->amount;
        $this->due_date = $obligation->due_date->toDateString();
        $this->account_id = $obligation->account_id;
        $this->category_id = $obligation->category_id;
        $this->notes = $obligation->notes;

        if ($obligation->installment_plan_id) {
            $this->isInstallment = true;
        }

        if ($obligation->status !== 'open') {
            $this->isPaidOrCancelled = true;
        }
    }

    public function rules()
    {
        $rules = [
            'notes' => 'nullable|string',
        ];

        if (! $this->isInstallment && ! $this->isPaidOrCancelled) {
            $rules = array_merge($rules, [
                'title' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0.01',
                'due_date' => 'required|date',
                'account_id' => 'required|exists:accounts,id',
                'category_id' => 'required|exists:categories,id',
            ]);
        }

        return $rules;
    }

    public function save(FinancialObligationService $service)
    {
        $this->validate();

        $data = [
            'title' => $this->title,
            'amount' => $this->amount,
            'due_date' => $this->due_date,
            'account_id' => $this->account_id,
            'category_id' => $this->category_id,
            'notes' => $this->notes,
        ];

        try {
            if ($this->obligationId) {
                $obligation = FinancialObligation::findOrFail($this->obligationId);
                $service->updatePayable($obligation, $data);
                $this->dispatch('payableUpdated');
            } else {
                $service->createPayable($data);
                $this->dispatch('payableCreated');
            }

            $this->isOpen = false;
        } catch (\Exception $e) {
            $this->addError('general', $e->getMessage());
        }
    }

    public function render()
    {
        $accounts = Account::where('user_id', auth()->id())->orderBy('name')->get();
        $categories = Category::where(function ($q) {
            $q->where('user_id', auth()->id())->orWhereNull('user_id');
        })->where('type', 'expense')->orderBy('name')->get();

        return view('livewire.payables.payable-form', [
            'accounts' => $accounts,
            'categories' => $categories,
        ]);
    }
}
