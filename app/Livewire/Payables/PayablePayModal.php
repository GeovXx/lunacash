<?php

namespace App\Livewire\Payables;

use App\Models\Account;
use App\Models\FinancialObligation;
use App\Services\FinancialObligationService;
use Livewire\Component;

class PayablePayModal extends Component
{
    public $isOpen = false;

    public $obligationId = null;

    public $amount;

    public $transaction_date;

    public $account_id;

    public $title;

    public $expected_amount;

    protected $listeners = ['openPayablePayModal'];

    public function openPayablePayModal($data)
    {
        $this->resetValidation();
        $this->reset(['obligationId', 'amount', 'transaction_date', 'account_id', 'title', 'expected_amount']);

        $this->obligationId = $data['obligationId'];

        $obligation = FinancialObligation::where('id', $this->obligationId)
            ->where('user_id', auth()->id())
            ->where('direction', 'payable')
            ->where('status', 'open')
            ->firstOrFail();

        $this->title = $obligation->title;
        $this->expected_amount = (float) $obligation->amount;
        $this->amount = $obligation->amount;
        $this->account_id = $obligation->account_id;
        $this->transaction_date = now()->toDateString();

        $this->isOpen = true;
    }

    public function rules()
    {
        return [
            'amount' => 'required|numeric|in:'.$this->expected_amount,
            'transaction_date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
        ];
    }

    public function messages()
    {
        return [
            'amount.in' => 'O valor do pagamento deve ser exatamente igual ao valor da obrigação.',
        ];
    }

    public function pay(FinancialObligationService $service)
    {
        $this->validate();

        try {
            $obligation = FinancialObligation::where('id', $this->obligationId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            $service->payObligation($obligation, [
                'user_id' => auth()->id(),
                'amount' => $this->amount,
                'transaction_date' => $this->transaction_date,
                'account_id' => $this->account_id,
            ]);

            $this->isOpen = false;
            $this->dispatch('payablePaid');
        } catch (\Exception $e) {
            $this->addError('general', $e->getMessage());
        }
    }

    public function render()
    {
        $accounts = Account::where('user_id', auth()->id())->orderBy('name')->get();

        return view('livewire.payables.payable-pay-modal', [
            'accounts' => $accounts,
        ]);
    }
}
