<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Despesas'])]
class Expenses extends Component
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
        $this->dispatch('open-expense-form');
    }

    public function edit(string $id): void
    {
        $expense = Transaction::query()->forUser()->where('type', 'expense')->findOrFail($id);
        $this->authorize('update', $expense);

        $this->editingId = $expense->id;
        $this->accountId = $expense->account_id;
        $this->categoryId = $expense->category_id;
        $this->amount = (string) $expense->amount;
        $this->transactionDate = $expense->transaction_date->toDateString();
        $this->status = $expense->status;
        $this->reference = $expense->reference;
        $this->description = $expense->description;

        $this->dispatch('open-expense-form');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'accountId' => ['required', 'uuid', Rule::exists('accounts', 'id')->where('user_id', auth()->id())],
            'categoryId' => [
                'nullable',
                'uuid',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->where('type', 'expense')
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
            'type' => 'expense',
            'amount' => $validated['amount'],
            'currency' => 'BRL',
            'transaction_date' => $validated['transactionDate'],
            'status' => $validated['status'],
            'reference' => $validated['reference'],
            'description' => $validated['description'],
        ];

        if ($this->editingId) {
            $expense = Transaction::query()->forUser()->where('type', 'expense')->findOrFail($this->editingId);
            $this->authorize('update', $expense);
            $expense->update($payload);
        } else {
            $this->authorize('create', Transaction::class);
            Transaction::create($payload);
        }

        $this->dispatch('close-expense-form');
        $this->resetForm();
    }

    public function delete(string $id): void
    {
        $expense = Transaction::query()->forUser()->where('type', 'expense')->findOrFail($id);
        $this->authorize('delete', $expense);

        $expense->delete();
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

        return view('livewire.expenses', [
            'expenses' => Transaction::query()->forUser()->where('type', 'expense')
                ->with(['account', 'category'])
                ->orderByDesc('transaction_date')
                ->orderByDesc('created_at')
                ->get(),
            'accounts' => Account::query()->forUser()->orderBy('name')->get(),
            'categories' => Category::query()->visibleTo($user)->where('type', 'expense')->orderBy('name')->get(),
            'statuses' => self::STATUSES,
        ]);
    }
}
