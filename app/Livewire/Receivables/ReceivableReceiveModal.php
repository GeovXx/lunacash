<?php

namespace App\Livewire\Receivables;

use App\Models\Account;
use App\Models\FinancialObligation;
use App\Services\FinancialObligationService;
use Livewire\Component;

class ReceivableReceiveModal extends Component
{
    public $isOpen = false;

    public $obligationId = null;

    public $title;

    public $expected_amount;

    public $amount;

    public $transaction_date;

    public $account_id;

    protected $listeners = ['openReceivableReceiveModal'];

    public function openReceivableReceiveModal($data = null)
    {
        $this->resetValidation();
        $this->reset(['obligationId', 'title', 'expected_amount', 'amount', 'transaction_date', 'account_id']);

        if (isset($data['obligationId'])) {
            $this->obligationId = $data['obligationId'];
            $this->loadObligation();
            $this->isOpen = true;
        }
    }

    public function loadObligation()
    {
        $obligation = FinancialObligation::where('id', $this->obligationId)
            ->where('user_id', auth()->id())
            ->where('direction', 'receivable')
            ->firstOrFail();

        $this->title = $obligation->title;
        $this->expected_amount = $obligation->amount;
        $this->amount = $obligation->amount; // Prefill with exact amount
        $this->transaction_date = now()->toDateString();
        $this->account_id = $obligation->account_id; // Prefill with original account
    }

    public function rules()
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
        ];
    }

    public function receive(FinancialObligationService $service)
    {
        $this->validate();

        try {
            $obligation = FinancialObligation::findOrFail($this->obligationId);

            $service->receiveObligation($obligation, [
                'user_id' => auth()->id(),
                'amount' => $this->amount,
                'transaction_date' => $this->transaction_date,
                'account_id' => $this->account_id,
            ]);

            $this->dispatch('receivableReceived');
            $this->isOpen = false;
        } catch (\Exception $e) {
            $this->addError('general', $e->getMessage());
        }
    }

    public function render()
    {
        $accounts = Account::where('user_id', auth()->id())->orderBy('name')->get();

        return view('livewire.receivables.receivable-receive-modal', [
            'accounts' => $accounts,
        ]);
    }
}
