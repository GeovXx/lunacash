<?php

namespace App\Livewire\Receivables;

use App\Models\FinancialObligation;
use Livewire\Component;
use Livewire\WithPagination;

class ReceivablesList extends Component
{
    use WithPagination;

    public $status = 'open';

    protected $listeners = [
        'receivableCreated' => '$refresh',
        'receivableUpdated' => '$refresh',
        'receivableReceived' => '$refresh',
    ];

    public function render()
    {
        $receivables = FinancialObligation::with(['category', 'account', 'installmentPlan'])
            ->where('user_id', auth()->id())
            ->where('direction', 'receivable');

        if ($this->status !== 'all') {
            $receivables->where('status', $this->status);
        }

        $receivables = $receivables->orderBy('due_date', 'asc')->paginate(15);

        return view('livewire.receivables.receivables-list', [
            'receivables' => $receivables,
        ]);
    }

    public function setStatus($status)
    {
        $this->status = $status;
        $this->resetPage();
    }
}
