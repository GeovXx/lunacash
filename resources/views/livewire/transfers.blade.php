<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-h2 font-semibold text-foreground">Transferências</h2>
            <p class="text-sm text-foreground-muted">Gerencie movimentações financeiras entre suas contas.</p>
        </div>
        <x-ui.button variant="primary" wire:click="create">
            <x-lucide-plus class="h-4 w-4 mr-2" />
            Nova Transferência
        </x-ui.button>
    </div>

    @if ($transfers->isEmpty())
        <x-ui.empty-state
            icon="arrow-right-left"
            title="Nenhuma transferência registrada"
            description="Transfira dinheiro entre suas contas para manter o controle absoluto de seus saldos."
            action-label="Nova Transferência"
            action-click="create"
        />
    @else
        <div class="space-y-4">
            <x-ui.card>
                <ul class="divide-y divide-border">
                    @foreach ($transfers as $transfer)
                        <li class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 py-3 px-4" wire:key="transfer-{{ $transfer->id }}">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <x-lucide-arrow-right-left class="h-4 w-4 text-foreground-muted" />
                                    <p class="truncate font-medium text-foreground">
                                        {{ $transfer->description ?: 'Transferência entre contas' }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                    <span class="text-xs text-foreground-muted">{{ $transfer->transfer_date->format('d/m/Y') }}</span>
                                    <span class="text-xs text-foreground-muted">&bull;</span>
                                    <span class="text-xs font-semibold text-destructive">- {{ $transfer->fromAccount->name }}</span>
                                    <x-lucide-arrow-right class="h-3 w-3 text-foreground-muted mx-1" />
                                    <span class="text-xs font-semibold text-success">+ {{ $transfer->toAccount->name }}</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between sm:justify-end gap-4 w-full sm:w-auto">
                                <div class="flex flex-col sm:items-end gap-1">
                                    <span class="font-semibold text-foreground">R$ {{ number_format($transfer->amount, 2, ',', '.') }}</span>
                                    
                                    <div class="flex flex-wrap items-center gap-1 mt-1">
                                        <x-ui.badge variant="{{ $transfer->status === 'completed' ? 'success' : ($transfer->status === 'cancelled' ? 'neutral' : 'warning') }}">
                                            {{ $statuses[$transfer->status] }}
                                        </x-ui.badge>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1 sm:ml-4">
                                    <x-ui.button variant="ghost" size="sm" class="h-9 w-9 p-0 justify-center" wire:click="edit('{{ $transfer->id }}')" aria-label="Editar transferência">
                                        <x-lucide-pencil class="h-4 w-4" />
                                    </x-ui.button>
                                    <x-ui.button
                                        variant="ghost"
                                        size="sm"
                                        class="h-9 w-9 p-0 justify-center"
                                        wire:click="delete('{{ $transfer->id }}')"
                                        wire:confirm="Tem certeza que deseja excluir esta transferência? Os saldos das contas serão restaurados automaticamente."
                                        aria-label="Excluir transferência"
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
                {{ $transfers->links() }}
            </div>
        </div>
    @endif

    <x-ui.modal name="transfer-form" title="{{ $editingId ? 'Editar Transferência' : 'Nova Transferência' }}">
        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-forms.label for="fromAccountId" required>Conta Origem (-)</x-forms.label>
                    <x-forms.select
                        wire:model="fromAccountId"
                        id="fromAccountId"
                        name="fromAccountId"
                        :options="$accounts->pluck('name', 'id')->toArray()"
                    />
                    <x-forms.error name="fromAccountId" />
                </div>
                <div>
                    <x-forms.label for="toAccountId" required>Conta Destino (+)</x-forms.label>
                    <x-forms.select
                        wire:model="toAccountId"
                        id="toAccountId"
                        name="toAccountId"
                        :options="$accounts->pluck('name', 'id')->toArray()"
                    />
                    <x-forms.error name="toAccountId" />
                </div>
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
                <x-forms.label for="transferDate" required>Data da transferência</x-forms.label>
                <x-forms.input wire:model="transferDate" id="transferDate" name="transferDate" type="date" />
                <x-forms.error name="transferDate" />
            </div>

            <div>
                <x-forms.label for="status" required>Status</x-forms.label>
                <x-forms.select wire:model="status" id="status" name="status" :options="$statuses" />
                <x-forms.error name="status" />
            </div>

            <div>
                <x-forms.label for="description">Descrição</x-forms.label>
                <x-forms.input wire:model="description" id="description" name="description" placeholder="Ex: Transferência para reserva de emergência" />
                <x-forms.error name="description" />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <x-ui.button type="button" variant="ghost" x-on:click="open = false">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary" loading wire:target="save">Salvar</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
