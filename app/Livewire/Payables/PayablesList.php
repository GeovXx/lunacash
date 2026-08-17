<?php

namespace App\Livewire\Payables;

use App\Models\FinancialObligation;
use Livewire\Component;
use Livewire\WithPagination;

class PayablesList extends Component
{
    use WithPagination;

    public $status = 'open';

    protected $listeners = [
        'payableCreated' => '$refresh',
        'payableUpdated' => '$refresh',
        'payablePaid' => '$refresh',
    ];

    public function render()
    {
        $payables = FinancialObligation::with(['category', 'account', 'installmentPlan'])
            ->where('user_id', auth()->id())
            ->where('direction', 'payable');

        if ($this->status !== 'all') {
            $payables->where('status', $this->status);
        }

        $payables = $payables->orderBy('due_date', 'asc')->paginate(15);

        return view('livewire.payables.payables-list', [
            'payables' => $payables,
        ]);
    }

    public function setStatus($status)
    {
        $this->status = $status;
        $this->resetPage();
    }
}
