<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-h2 font-semibold text-foreground">Lançamentos</h2>
            <p class="text-sm text-foreground-muted">Visão geral de todas as suas movimentações financeiras.</p>
        </div>
    </div>

    <x-ui.card class="p-4 space-y-4">
        <div class="mb-4">
            <x-forms.label for="filterSearch" class="sr-only">Pesquisar</x-forms.label>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-foreground-muted">
                    <x-lucide-search class="h-4 w-4" />
                </div>
                <x-forms.input wire:model.live.debounce.500ms="filterSearch" id="filterSearch" type="search" placeholder="Pesquisar por descrição ou referência..." class="pl-10 w-full" />
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <div>
                <x-forms.label for="filterStartDate">Data Inicial</x-forms.label>
                <x-forms.input wire:model.live="filterStartDate" id="filterStartDate" type="date" />
            </div>
            <div>
                <x-forms.label for="filterEndDate">Data Final</x-forms.label>
                <x-forms.input wire:model.live="filterEndDate" id="filterEndDate" type="date" />
            </div>
            <div>
                <x-forms.label for="filterAccountId">Conta</x-forms.label>
                <x-forms.select wire:model.live="filterAccountId" id="filterAccountId" placeholder="Todas as contas" :options="$accounts->pluck('name', 'id')->toArray()" />
            </div>
            <div>
                <x-forms.label for="filterCategoryId">Categoria</x-forms.label>
                <x-forms.select wire:model.live="filterCategoryId" id="filterCategoryId" placeholder="Todas as categorias" :options="$categories->pluck('name', 'id')->toArray()" />
            </div>
            <div>
                <x-forms.label for="filterType">Tipo</x-forms.label>
                <x-forms.select wire:model.live="filterType" id="filterType" placeholder="Todos os tipos" :options="$types" />
            </div>
            <div>
                <x-forms.label for="filterStatus">Status</x-forms.label>
                <x-forms.select wire:model.live="filterStatus" id="filterStatus" placeholder="Todos os status" :options="$statuses" />
            </div>
        </div>
        <div class="flex justify-end">
            <x-ui.button variant="ghost" size="sm" wire:click="clearFilters">Limpar Filtros</x-ui.button>
        </div>
    </x-ui.card>

    @if ($transactions->isEmpty())
        <x-ui.empty-state
            icon="list"
            title="Nenhum lançamento encontrado"
            description="Tente ajustar os filtros ou registre novas movimentações nos módulos de Receitas ou Despesas."
        />
    @else
        <div class="space-y-4">
            <x-ui.card>
                <ul class="divide-y divide-border">
                    @foreach ($transactions as $transaction)
                        <li class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 py-3 px-4" wire:key="transaction-{{ $transaction->id }}">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-foreground">
                                    {{ $transaction->description ?: 'Lançamento sem descrição' }}
                                </p>
                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                    <span class="text-xs text-foreground-muted">{{ $transaction->transaction_date->format('d/m/Y') }}</span>
                                    <span class="text-xs text-foreground-muted">&bull;</span>
                                    <span class="text-xs text-foreground-muted">{{ $transaction->account->name }}</span>
                                    @if ($transaction->category)
                                        <span class="text-xs text-foreground-muted">&bull;</span>
                                        <span class="text-xs text-foreground-muted">{{ $transaction->category->name }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center justify-between sm:justify-end gap-4 w-full sm:w-auto">
                                <div class="flex flex-col sm:items-end gap-1">
                                    @if($transaction->type === 'income' || $transaction->type === 'refund')
                                        <span class="font-semibold text-success">+ R$ {{ number_format($transaction->amount, 2, ',', '.') }}</span>
                                    @elseif($transaction->type === 'expense' || $transaction->type === 'payment')
                                        <span class="font-semibold text-destructive">- R$ {{ number_format($transaction->amount, 2, ',', '.') }}</span>
                                    @else
                                        <span class="font-semibold text-foreground">R$ {{ number_format($transaction->amount, 2, ',', '.') }}</span>
                                    @endif
                                    
                                    <div class="flex flex-wrap items-center gap-1 mt-1">
                                        <x-ui.badge variant="neutral" class="text-[10px] uppercase tracking-wider">
                                            {{ $types[$transaction->type] ?? $transaction->type }}
                                        </x-ui.badge>
                                        <x-ui.badge variant="{{ $transaction->status === 'posted' || $transaction->status === 'reconciled' ? 'success' : ($transaction->status === 'cancelled' ? 'neutral' : 'warning') }}">
                                            {{ $statuses[$transaction->status] }}
                                        </x-ui.badge>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1 sm:ml-4">
                                    <x-ui.button variant="ghost" size="sm" class="h-9 w-9 p-0 justify-center" wire:click="edit('{{ $transaction->id }}')" aria-label="Editar lançamento">
                                        <x-lucide-pencil class="h-4 w-4" />
                                    </x-ui.button>
                                    <x-ui.button
                                        variant="ghost"
                                        size="sm"
                                        class="h-9 w-9 p-0 justify-center"
                                        wire:click="delete('{{ $transaction->id }}')"
                                        wire:confirm="Tem certeza que deseja excluir este lançamento?"
                                        aria-label="Excluir lançamento"
                                    >
                                        <x-lucide-trash-2 class="h-4 w-4" />
                                    </x-ui.button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>
            
            <div class="mt-4">
                {{ $transactions->links() }}
            </div>
        </div>
    @endif

    <x-ui.modal name="transaction-form" title="Editar lançamento">
        @if($editingId)
            <form wire:submit="save" class="space-y-4">
                <div>
                    <x-forms.label for="accountId" required>Conta</x-forms.label>
                    <x-forms.select
                        wire:model="accountId"
                        id="accountId"
                        name="accountId"
                        :options="$accounts->pluck('name', 'id')->toArray()"
                    />
                    <x-forms.error name="accountId" />
                </div>

                <div>
                    <x-forms.label for="categoryId">Categoria (opcional)</x-forms.label>
                    <x-forms.select
                        wire:model="categoryId"
                        id="categoryId"
                        name="categoryId"
                        placeholder="Sem categoria"
                        :options="$editingCategories->pluck('name', 'id')->toArray()"
                    />
                    <p class="text-xs text-foreground-muted mt-1">Apenas categorias compatíveis com o tipo original da transação são listadas.</p>
                    <x-forms.error name="categoryId" />
                </div>

                <div>
                    <x-forms.label for="amount" required>Valor</x-forms.label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="text-foreground-muted sm:text-sm">R$</span>
                        </div>
                        <x-forms.input wire:model="amount" id="amount" name="amount" type="number" step="0.01" min="0.01" class="pl-10" placeholder="0,00" />
                    </div>
                    <x-forms.error name="amount" />
                </div>

                <div>
                    <x-forms.label for="transactionDate" required>Data da transação</x-forms.label>
                    <x-forms.input wire:model="transactionDate" id="transactionDate" name="transactionDate" type="date" />
                    <x-forms.error name="transactionDate" />
                </div>

                <div>
                    <x-forms.label for="status" required>Status</x-forms.label>
                    <x-forms.select wire:model="status" id="status" name="status" :options="$statuses" />
                    <x-forms.error name="status" />
                </div>

                <div>
                    <x-forms.label for="description">Descrição</x-forms.label>
                    <x-forms.input wire:model="description" id="description" name="description" placeholder="Ex: Conta de luz" />
                    <x-forms.error name="description" />
                </div>

                <div>
                    <x-forms.label for="reference">Referência (opcional)</x-forms.label>
                    <x-forms.input wire:model="reference" id="reference" name="reference" placeholder="Ex: FAT-001" />
                    <x-forms.error name="reference" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <x-ui.button type="button" variant="ghost" x-on:click="open = false">Cancelar</x-ui.button>
                    <x-ui.button type="submit" variant="primary" loading wire:target="save">Salvar</x-ui.button>
                </div>
            </form>
        @endif
    </x-ui.modal>
</div>
