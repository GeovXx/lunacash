<?php

namespace App\Http\Livewire;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Receitas'])]
class Incomes extends Component
{
    public ?string $editingId = null;

    public ?string $accountId = null;

    public ?string $categoryId = null;

    public string $amount = '';

    public string $transactionDate = '';

    public string $status = 'posted';

    public ?string $reference = null;

    public ?string $description = null;

    private const STATUSES = [
        'pending' => 'Pendente',
        'posted' => 'Lançada',
        'reconciled' => 'Conciliada',
        'cancelled' => 'Cancelada',
    ];

    public function mount(): void
    {
        $this->transactionDate = now()->toDateString();
    }

    public function create(): void
    {
        $this->authorize('create', Transaction::class);

        $this->resetForm();
        $this->dispatch('open-income-form');
    }

    public function edit(string $id): void
    {
        $income = Transaction::query()->forUser()->where('type', 'income')->findOrFail($id);
        $this->authorize('update', $income);

        $this->editingId = $income->id;
        $this->accountId = $income->account_id;
        $this->categoryId = $income->category_id;
        $this->amount = (string) $income->amount;
        $this->transactionDate = $income->transaction_date->toDateString();
        $this->status = $income->status;
        $this->reference = $income->reference;
        $this->description = $income->description;

        $this->dispatch('open-income-form');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'accountId' => ['required', 'uuid', Rule::exists('accounts', 'id')->where('user_id', auth()->id())],
            'categoryId' => [
                'nullable',
                'uuid',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->where('type', 'income')
                        ->where(function ($q) {
                            $q->whereNull('user_id')->orWhere('user_id', auth()->id());
                        });
                }),
            ],
            'amount' => ['required', 'numeric', 'gt:0'],
            'transactionDate' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(self::STATUSES))],
            'reference' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $payload = [
            'account_id' => $validated['accountId'],
            'category_id' => $validated['categoryId'],
            'type' => 'income',
            'amount' => $validated['amount'],
            'currency' => 'BRL',
            'transaction_date' => $validated['transactionDate'],
            'status' => $validated['status'],
            'reference' => $validated['reference'],
            'description' => $validated['description'],
        ];

        if ($this->editingId) {
            $income = Transaction::query()->forUser()->where('type', 'income')->findOrFail($this->editingId);
            $this->authorize('update', $income);
            $income->update($payload);
        } else {
            $this->authorize('create', Transaction::class);
            Transaction::create($payload);
        }

        $this->dispatch('close-income-form');
        $this->resetForm();
    }

    public function delete(string $id): void
    {
        $income = Transaction::query()->forUser()->where('type', 'income')->findOrFail($id);
        $this->authorize('delete', $income);

        $income->delete();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'categoryId', 'amount', 'reference', 'description']);
        $this->accountId = Account::query()->forUser()->orderBy('name')->value('id');
        $this->transactionDate = now()->toDateString();
        $this->status = 'posted';
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.incomes', [
            'incomes' => Transaction::query()->forUser()->where('type', 'income')
                ->with(['account', 'category'])
                ->orderByDesc('transaction_date')
                ->orderByDesc('created_at')
                ->get(),
            'accounts' => Account::query()->forUser()->orderBy('name')->get(),
            'categories' => Category::query()->visibleTo($user)->where('type', 'income')->orderBy('name')->get(),
            'statuses' => self::STATUSES,
        ]);
    }
}
