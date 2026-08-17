<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-h2 font-semibold text-foreground">Despesas</h2>
            <p class="text-sm text-foreground-muted">Acompanhe suas saídas de dinheiro.</p>
        </div>
        <x-ui.button wire:click="create" variant="destructive">
            <x-lucide-minus class="h-4 w-4" />
            Nova despesa
        </x-ui.button>
    </div>

    @if ($expenses->isEmpty())
        <x-ui.empty-state
            icon="arrow-down-circle"
            title="Nenhuma despesa encontrada"
            description="Comece a registrar suas despesas."
        >
            <x-slot:action>
                <x-ui.button wire:click="create" variant="destructive">Criar despesa</x-ui.button>
            </x-slot:action>
        </x-ui.empty-state>
    @else
        <div class="space-y-4">
            <x-ui.card>
                <ul class="divide-y divide-border">
                    @foreach ($expenses as $expense)
                        <li class="flex items-center justify-between gap-3 py-3 px-4" wire:key="expense-{{ $expense->id }}">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-foreground">
                                    {{ $expense->description ?: 'Despesa sem descrição' }}
                                </p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs text-foreground-muted">{{ $expense->transaction_date->format('d/m/Y') }}</span>
                                    <span class="text-xs text-foreground-muted">&bull;</span>
                                    <span class="text-xs text-foreground-muted">{{ $expense->account->name }}</span>
                                    @if ($expense->category)
                                        <span class="text-xs text-foreground-muted">&bull;</span>
                                        <span class="text-xs text-foreground-muted">{{ $expense->category->name }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-col items-end gap-1">
                                <span class="font-semibold text-destructive">- R$ {{ number_format($expense->amount, 2, ',', '.') }}</span>
                                <x-ui.badge variant="{{ $expense->status === 'posted' || $expense->status === 'reconciled' ? 'success' : ($expense->status === 'cancelled' ? 'neutral' : 'warning') }}">
                                    {{ $statuses[$expense->status] }}
                                </x-ui.badge>
                            </div>

                            <div class="flex items-center gap-1 ml-4">
                                <x-ui.button variant="ghost" size="sm" wire:click="edit('{{ $expense->id }}')" aria-label="Editar despesa">
                                    <x-lucide-pencil class="h-4 w-4" />
                                </x-ui.button>
                                <x-ui.button
                                    variant="ghost"
                                    size="sm"
                                    wire:click="delete('{{ $expense->id }}')"
                                    wire:confirm="Tem certeza que deseja excluir esta despesa?"
                                    aria-label="Excluir despesa"
                                >
                                    <x-lucide-trash-2 class="h-4 w-4" />
                                </x-ui.button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>
        </div>
    @endif

    <x-ui.modal name="expense-form" :title="$editingId ? 'Editar despesa' : 'Nova despesa'">
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
                    :options="$categories->pluck('name', 'id')->toArray()"
                />
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
                <x-ui.button type="submit" variant="destructive" loading wire:target="save">Salvar</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
