<?php

namespace App\Http\Livewire;

use App\Models\Account;
use App\Models\Transfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Transferências'])]
class Transfers extends Component
{
    use WithPagination;

    public ?string $editingId = null;

    public ?string $fromAccountId = null;

    public ?string $toAccountId = null;

    public string $amount = '';

    public string $transferDate = '';

    public string $status = 'completed';

    public ?string $description = null;

    private const STATUSES = [
        'pending' => 'Pendente',
        'completed' => 'Concluída',
        'cancelled' => 'Cancelada',
    ];

    public function mount(): void
    {
        // ...
    }

    public function create(): void
    {
        $this->authorize('create', Transfer::class);

        $this->resetForm();
        $this->dispatch('open-transfer-form');
    }

    public function edit(string $id): void
    {
        $transfer = Transfer::query()->forUser()->findOrFail($id);
        $this->authorize('update', $transfer);

        $this->editingId = $transfer->id;
        $this->fromAccountId = $transfer->from_account_id;
        $this->toAccountId = $transfer->to_account_id;
        $this->amount = (string) $transfer->amount;
        $this->transferDate = $transfer->transfer_date->toDateString();
        $this->status = $transfer->status;
        $this->description = $transfer->description;

        $this->dispatch('open-transfer-form');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'fromAccountId' => ['required', 'uuid', Rule::exists('accounts', 'id')->where('user_id', auth()->id())],
            'toAccountId' => ['required', 'uuid', 'different:fromAccountId', Rule::exists('accounts', 'id')->where('user_id', auth()->id())],
            'amount' => ['required', 'numeric', 'gt:0'],
            'transferDate' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(self::STATUSES))],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $payload = [
            'from_account_id' => $validated['fromAccountId'],
            'to_account_id' => $validated['toAccountId'],
            'amount' => $validated['amount'],
            'currency' => 'BRL', // Default for now
            'transfer_date' => $validated['transferDate'],
            'status' => $validated['status'],
            'description' => $validated['description'],
        ];

        DB::transaction(function () use ($payload) {
            if ($this->editingId) {
                $transfer = Transfer::query()->forUser()->findOrFail($this->editingId);
                $this->authorize('update', $transfer);
                $transfer->update($payload);
            } else {
                $this->authorize('create', Transfer::class);
                Transfer::create($payload);
            }
        });

        $this->dispatch('close-transfer-form');
        $this->resetForm();
    }

    public function delete(string $id): void
    {
        $transfer = Transfer::query()->forUser()->findOrFail($id);
        $this->authorize('delete', $transfer);

        $transfer->delete();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'fromAccountId', 'toAccountId', 'amount', 'transferDate', 'description']);
        $this->status = 'completed';
        $this->transferDate = now()->toDateString();
    }

    public function render()
    {
        $transfers = Transfer::query()
            ->forUser()
            ->with(['fromAccount', 'toAccount'])
            ->orderByDesc('transfer_date')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.transfers', [
            'transfers' => $transfers,
            'accounts' => Account::query()->forUser()->orderBy('name')->get(),
            'statuses' => self::STATUSES,
        ]);
    }
}
