<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $search = '';

    public array $results = [
        'accounts' => [],
        'categories' => [],
        'credit_cards' => [],
        'transactions' => [],
    ];

    public function updatedSearch(): void
    {
        $this->results = [
            'accounts' => [],
            'categories' => [],
            'credit_cards' => [],
            'transactions' => [],
        ];

        $term = trim($this->search);

        if (strlen($term) < 3) {
            return; // Busca vazia ou menor que 3 caracteres = limpa resultados e aborta query pesada
        }

        $user = auth()->user();
        $searchTerm = '%'.strtolower($term).'%';
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $likeOperator = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';

        // Contas
        $accounts = Account::query()
            ->forUser()
            ->where(function ($q) use ($searchTerm, $likeOperator) {
                $q->where('name', $likeOperator, $searchTerm)
                    ->orWhere('institution', $likeOperator, $searchTerm);
            })
            ->take(5)
            ->get(['id', 'name', 'institution'])
            ->toArray();

        // Categorias
        $categories = Category::query()
            ->visibleTo($user)
            ->where('name', $likeOperator, $searchTerm)
            ->take(5)
            ->get(['id', 'name', 'type', 'color'])
            ->toArray();

        // Cartões
        $creditCards = CreditCard::query()
            ->forUser()
            ->where('name', $likeOperator, $searchTerm)
            ->take(5)
            ->get(['id', 'name'])
            ->toArray();

        // Lançamentos
        $transactions = Transaction::query()
            ->forUser()
            ->with(['account:id,name', 'category:id,name,color'])
            ->where(function ($q) use ($searchTerm, $likeOperator) {
                $q->where('description', $likeOperator, $searchTerm)
                    ->orWhere('reference', $likeOperator, $searchTerm);
            })
            ->take(5)
            ->get(['id', 'description', 'reference', 'amount', 'type', 'transaction_date', 'account_id', 'category_id'])
            ->toArray();

        $this->results = [
            'accounts' => $accounts,
            'categories' => $categories,
            'credit_cards' => $creditCards,
            'transactions' => $transactions,
        ];
    }

    public function clear(): void
    {
        $this->reset('search', 'results');
    }

    public function render()
    {
        return view('livewire.global-search');
    }
}
