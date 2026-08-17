<?php

namespace App\Http\Livewire;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Lançamentos'])]
class Transactions extends Component
{
    use WithPagination;

    public ?string $editingId = null;

    public ?string $accountId = null;

    public ?string $categoryId = null;

    public string $amount = '';

    public string $transactionDate = '';

    public string $status = 'posted';

    public ?string $reference = null;

    public ?string $description = null;

    // Filters
    public string $filterStartDate = '';

    public string $filterEndDate = '';

    public string $filterAccountId = '';

    public string $filterCategoryId = '';

    public string $filterType = '';

    public string $filterStatus = '';

    public string $filterSearch = '';

    private const STATUSES = [
        'pending' => 'Pendente',
        'posted' => 'Lançada',
        'reconciled' => 'Conciliada',
        'cancelled' => 'Cancelada',
    ];

    private const TYPES = [
        'income' => 'Receita',
        'expense' => 'Despesa',
        'adjustment' => 'Ajuste',
        'transfer' => 'Transferência',
        'payment' => 'Pagamento',
        'refund' => 'Reembolso',
    ];

    public function updated($propertyName): void
    {
        if (str_starts_with($propertyName, 'filter')) {
            $this->resetPage();
        }
    }

    public function mount(): void
    {
        $this->filterStartDate = now()->startOfMonth()->toDateString();
        $this->filterEndDate = now()->endOfMonth()->toDateString();
    }

    public function edit(string $id): void
    {
        $transaction = Transaction::query()->forUser()->findOrFail($id);
        $this->authorize('update', $transaction);

        $this->editingId = $transaction->id;
        $this->accountId = $transaction->account_id;
        $this->categoryId = $transaction->category_id;
        $this->amount = (string) $transaction->amount;
        $this->transactionDate = $transaction->transaction_date->toDateString();
        $this->status = $transaction->status;
        $this->reference = $transaction->reference;
        $this->description = $transaction->description;

        $this->dispatch('open-transaction-form');
    }

    public function save(): void
    {
        $transaction = Transaction::query()->forUser()->findOrFail($this->editingId);
        $this->authorize('update', $transaction);

        $validated = $this->validate([
            'accountId' => ['required', 'uuid', Rule::exists('accounts', 'id')->where('user_id', auth()->id())],
            'categoryId' => [
                'nullable',
                'uuid',
                Rule::exists('categories', 'id')->where(function ($query) use ($transaction) {
                    $query->where('type', $transaction->type)
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

        $transaction->update([
            'account_id' => $validated['accountId'],
            'category_id' => $validated['categoryId'],
            'amount' => $validated['amount'],
            'transaction_date' => $validated['transactionDate'],
            'status' => $validated['status'],
            'reference' => $validated['reference'],
            'description' => $validated['description'],
        ]);

        $this->dispatch('close-transaction-form');
        $this->resetForm();
    }

    public function delete(string $id): void
    {
        $transaction = Transaction::query()->forUser()->findOrFail($id);
        $this->authorize('delete', $transaction);

        $transaction->delete();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'accountId', 'categoryId', 'amount', 'transactionDate', 'status', 'reference', 'description']);
    }

    public function clearFilters(): void
    {
        $this->reset(['filterStartDate', 'filterEndDate', 'filterAccountId', 'filterCategoryId', 'filterType', 'filterStatus', 'filterSearch']);
        $this->filterStartDate = now()->startOfMonth()->toDateString();
        $this->filterEndDate = now()->endOfMonth()->toDateString();
        $this->resetPage();
    }

    #[Computed]
    public function accounts()
    {
        return Account::query()->forUser()->orderBy('name')->get();
    }

    #[Computed]
    public function categories()
    {
        return Category::query()->visibleTo(auth()->user())->orderBy('name')->get();
    }

    public function render()
    {
        $user = auth()->user();

        $query = Transaction::query()
            ->forUser()
            ->with(['account', 'category']);

        if ($this->filterStartDate) {
            $query->where('transaction_date', '>=', $this->filterStartDate);
        }
        if ($this->filterEndDate) {
            $query->where('transaction_date', '<=', $this->filterEndDate);
        }
        if ($this->filterAccountId) {
            $query->where('account_id', $this->filterAccountId);
        }
        if ($this->filterCategoryId) {
            $query->where('category_id', $this->filterCategoryId);
        }
        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }
        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }
        if (! empty(trim($this->filterSearch))) {
            $query->where(function ($q) {
                $search = '%'.strtolower(trim($this->filterSearch)).'%';
                $q->where(DB::raw('lower(description)'), 'like', $search)
                    ->orWhere(DB::raw('lower(reference)'), 'like', $search);
            });
        }

        $transactions = $query
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->paginate(20);

        // Fetch categories dynamically based on the edited transaction type
        $editingCategories = collect();
        if ($this->editingId) {
            $transaction = Transaction::query()->forUser()->find($this->editingId);
            if ($transaction) {
                $editingCategories = Category::query()
                    ->visibleTo($user)
                    ->where('type', $transaction->type)
                    ->orderBy('name')
                    ->get();
            }
        }

        return view('livewire.transactions', [
            'transactions' => $transactions,
            'accounts' => $this->accounts(),
            'categories' => $this->categories(),
            'editingCategories' => $editingCategories,
            'statuses' => self::STATUSES,
            'types' => self::TYPES,
        ]);
    }
}
