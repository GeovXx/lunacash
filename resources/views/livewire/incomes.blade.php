<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-h2 font-semibold text-foreground">Receitas</h2>
            <p class="text-sm text-foreground-muted">Acompanhe suas entradas de dinheiro.</p>
        </div>
        <x-ui.button wire:click="create">
            <x-lucide-plus class="h-4 w-4" />
            Nova receita
        </x-ui.button>
    </div>

    @if ($incomes->isEmpty())
        <x-ui.empty-state
            icon="trending-up"
            title="Nenhuma receita encontrada"
            description="Comece a registrar suas receitas."
        >
            <x-slot:action>
                <x-ui.button wire:click="create">Criar receita</x-ui.button>
            </x-slot:action>
        </x-ui.empty-state>
    @else
        <div class="space-y-4">
            <x-ui.card>
                <ul class="divide-y divide-border">
                    @foreach ($incomes as $income)
                        <li class="flex items-center justify-between gap-3 py-3 px-4" wire:key="income-{{ $income->id }}">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-foreground">
                                    {{ $income->description ?: 'Receita sem descrição' }}
                                </p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs text-foreground-muted">{{ $income->transaction_date->format('d/m/Y') }}</span>
                                    <span class="text-xs text-foreground-muted">&bull;</span>
                                    <span class="text-xs text-foreground-muted">{{ $income->account->name }}</span>
                                    @if ($income->category)
                                        <span class="text-xs text-foreground-muted">&bull;</span>
                                        <span class="text-xs text-foreground-muted">{{ $income->category->name }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-col items-end gap-1">
                                <span class="font-semibold text-success">R$ {{ number_format($income->amount, 2, ',', '.') }}</span>
                                <x-ui.badge variant="{{ $income->status === 'posted' || $income->status === 'reconciled' ? 'success' : ($income->status === 'cancelled' ? 'destructive' : 'neutral') }}">
                                    {{ $statuses[$income->status] }}
                                </x-ui.badge>
                            </div>

                            <div class="flex items-center gap-1 ml-4">
                                <x-ui.button variant="ghost" size="sm" wire:click="edit('{{ $income->id }}')" aria-label="Editar receita">
                                    <x-lucide-pencil class="h-4 w-4" />
                                </x-ui.button>
                                <x-ui.button
                                    variant="ghost"
                                    size="sm"
                                    wire:click="delete('{{ $income->id }}')"
                                    wire:confirm="Tem certeza que deseja excluir esta receita?"
                                    aria-label="Excluir receita"
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

    <x-ui.modal name="income-form" :title="$editingId ? 'Editar receita' : 'Nova receita'">
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
                <x-forms.input wire:model="description" id="description" name="description" placeholder="Ex: Salário do mês" />
                <x-forms.error name="description" />
            </div>

            <div>
                <x-forms.label for="reference">Referência (opcional)</x-forms.label>
                <x-forms.input wire:model="reference" id="reference" name="reference" placeholder="Ex: REC-001" />
                <x-forms.error name="reference" />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <x-ui.button type="button" variant="ghost" x-on:click="open = false">Cancelar</x-ui.button>
                <x-ui.button type="submit" loading wire:target="save">Salvar</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
