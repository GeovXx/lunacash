<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-h2 font-semibold text-foreground">Contas</h2>
            <p class="text-sm text-foreground-muted">Gerencie suas contas bancárias, carteiras e investimentos.</p>
        </div>
        <x-ui.button wire:click="create">
            <x-lucide-plus class="h-4 w-4" />
            Nova conta
        </x-ui.button>
    </div>

    @if ($accounts->isEmpty())
        <x-ui.empty-state
            icon="wallet"
            title="Nenhuma conta cadastrada"
            description="Adicione sua primeira conta para começar a organizar suas finanças."
        >
            <x-slot:action>
                <x-ui.button wire:click="create">Criar conta</x-ui.button>
            </x-slot:action>
        </x-ui.empty-state>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($accounts as $account)
                @php
                    $statusVariant = match ($account->status) {
                        'active' => 'success',
                        'archived' => 'warning',
                        default => 'danger',
                    };
                    $statusLabel = match ($account->status) {
                        'active' => 'Ativa',
                        'archived' => 'Arquivada',
                        default => 'Encerrada',
                    };
                @endphp
                <x-ui.card wire:key="account-{{ $account->id }}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-foreground">{{ $account->name }}</p>
                            <p class="truncate text-sm text-foreground-muted">{{ $account->institution ?: ($account->accountType?->name ?? '—') }}</p>
                        </div>
                        <x-ui.badge :variant="$statusVariant">{{ $statusLabel }}</x-ui.badge>
                    </div>

                    <x-ui.divider class="my-4" />

                    <div class="flex items-center justify-between text-sm">
                        <span class="text-foreground-muted">{{ $account->accountType?->name }}</span>
                        <div class="flex items-center gap-1">
                            <x-ui.button variant="ghost" size="sm" class="h-9 w-9 p-0 justify-center" wire:click="edit('{{ $account->id }}')" aria-label="Editar conta">
                                <x-lucide-pencil class="h-4 w-4" />
                            </x-ui.button>
                            <x-ui.button
                                variant="ghost"
                                size="sm"
                                class="h-9 w-9 p-0 justify-center"
                                wire:click="delete('{{ $account->id }}')"
                                wire:confirm="Tem certeza que deseja excluir esta conta?"
                                aria-label="Excluir conta"
                            >
                                <x-lucide-trash-2 class="h-4 w-4" />
                            </x-ui.button>
                        </div>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    @endif

    <x-ui.modal name="account-form" :title="$editingId ? 'Editar conta' : 'Nova conta'">
        <form wire:submit="save" class="space-y-4">
            <div>
                <x-forms.label for="name" required>Nome</x-forms.label>
                <x-forms.input wire:model="name" id="name" name="name" placeholder="Ex: Nubank" />
                <x-forms.error name="name" />
            </div>

            <div>
                <x-forms.label for="accountTypeId" required>Tipo de conta</x-forms.label>
                <x-forms.select
                    wire:model="accountTypeId"
                    id="accountTypeId"
                    name="accountTypeId"
                    :options="$accountTypes->pluck('name', 'id')->toArray()"
                />
                <x-forms.error name="accountTypeId" />
            </div>

            <div>
                <x-forms.label for="institution">Instituição</x-forms.label>
                <x-forms.input wire:model="institution" id="institution" name="institution" placeholder="Ex: Nu Pagamentos S.A." />
                <x-forms.error name="institution" />
            </div>

            <div>
                <x-forms.label for="accountNumber">Número da conta (opcional)</x-forms.label>
                <x-forms.input wire:model="accountNumber" id="accountNumber" name="accountNumber" />
                <x-forms.error name="accountNumber" />
            </div>

            @if ($editingId)
                <div>
                    <x-forms.label for="status" required>Status</x-forms.label>
                    <x-forms.select
                        wire:model="status"
                        id="status"
                        name="status"
                        :options="['active' => 'Ativa', 'archived' => 'Arquivada', 'closed' => 'Encerrada']"
                    />
                    <x-forms.error name="status" />
                </div>
            @endif

            <div class="flex justify-end gap-2 pt-2">
                <x-ui.button type="button" variant="ghost" x-on:click="open = false">Cancelar</x-ui.button>
                <x-ui.button type="submit" loading wire:target="save">Salvar</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
