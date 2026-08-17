<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-h2 font-semibold text-foreground">Recorrências</h2>
            <p class="text-sm text-foreground-muted">Gerencie suas despesas e receitas recorrentes (assinaturas, salários, etc).</p>
        </div>
        <x-ui.button variant="primary" wire:click="create">
            <x-lucide-plus class="h-4 w-4 mr-2" />
            Nova Recorrência
        </x-ui.button>
    </div>

    @if ($profiles->isEmpty())
        <x-ui.empty-state
            icon="repeat"
            title="Nenhuma recorrência registrada"
            description="Cadastre assinaturas e contas fixas para que o LunaCash lance automaticamente para você."
            action-label="Nova Recorrência"
            action-click="create"
        />
    @else
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($profiles as $profile)
                    <x-ui.card class="flex flex-col" wire:key="profile-{{ $profile->id }}">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg {{ $profile->type === 'income' ? 'bg-success/10 text-success' : 'bg-destructive/10 text-destructive' }}">
                                    @if($profile->type === 'income')
                                        <x-lucide-arrow-up-circle class="h-5 w-5" />
                                    @else
                                        <x-lucide-arrow-down-circle class="h-5 w-5" />
                                    @endif
                                </div>
                                <div>
                                    <h3 class="font-semibold text-foreground leading-tight">{{ $profile->name }}</h3>
                                    <p class="text-xs text-foreground-muted mt-0.5">{{ $profile->category->name }} &bull; {{ $profile->account->name }}</p>
                                </div>
                            </div>
                            <x-ui.badge variant="{{ $profile->status === 'active' ? 'success' : ($profile->status === 'paused' ? 'warning' : 'neutral') }}">
                                {{ $statuses[$profile->status] }}
                            </x-ui.badge>
                        </div>
                        
                        <div class="flex-1 space-y-3 mb-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-foreground-muted">Valor:</span>
                                <span class="font-bold text-foreground">R$ {{ number_format($profile->amount, 2, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-foreground-muted">Frequência:</span>
                                <span class="text-foreground font-medium">{{ $frequencies[$profile->frequency] }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-foreground-muted">Próxima Ocorrência:</span>
                                <span class="text-foreground font-medium {{ clone $profile->next_occurrence_date <= today() ? 'text-warning font-bold' : '' }}">
                                    {{ $profile->next_occurrence_date->format('d/m/Y') }}
                                </span>
                            </div>
                            @if($profile->end_date)
                                <div class="flex justify-between text-sm">
                                    <span class="text-foreground-muted">Fim previsto:</span>
                                    <span class="text-foreground font-medium">{{ $profile->end_date->format('d/m/Y') }}</span>
                                </div>
                            @endif
                        </div>
                        
                        <div class="flex items-center gap-2 pt-3 border-t border-border mt-auto">
                            <x-ui.button variant="ghost" size="sm" class="flex-1" wire:click="edit('{{ $profile->id }}')">
                                <x-lucide-pencil class="h-4 w-4 mr-2" />
                                Editar
                            </x-ui.button>
                            @if(in_array($profile->status, ['active', 'paused']))
                                <x-ui.button variant="ghost" size="sm" class="flex-1" wire:click="togglePause('{{ $profile->id }}')">
                                    @if($profile->status === 'active')
                                        <x-lucide-pause class="h-4 w-4 mr-2" />
                                        Pausar
                                    @else
                                        <x-lucide-play class="h-4 w-4 mr-2" />
                                        Retomar
                                    @endif
                                </x-ui.button>
                            @endif
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
            
            <div class="mt-4">
                {{ $profiles->links() }}
            </div>
        </div>
    @endif

    <x-ui.modal name="recurring-profile-form" title="{{ $editingId ? 'Editar Recorrência' : 'Nova Recorrência' }}">
        <form wire:submit="save" class="space-y-4">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-forms.label for="type" required>Tipo de Lançamento</x-forms.label>
                    <x-forms.select
                        wire:model.live="type"
                        id="type"
                        name="type"
                        :options="['income' => 'Receita (+)', 'expense' => 'Despesa (-)']"
                    />
                    <x-forms.error name="type" />
                </div>
                <div>
                    <x-forms.label for="name" required>Nome</x-forms.label>
                    <x-forms.input wire:model="name" id="name" name="name" placeholder="Ex: Netflix, Salário..." />
                    <x-forms.error name="name" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                    <x-forms.label for="categoryId" required>Categoria</x-forms.label>
                    <x-forms.select
                        wire:model="categoryId"
                        id="categoryId"
                        name="categoryId"
                        :options="$categories->pluck('name', 'id')->toArray()"
                    />
                    <x-forms.error name="categoryId" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                    <x-forms.label for="frequency" required>Frequência</x-forms.label>
                    <x-forms.select
                        wire:model="frequency"
                        id="frequency"
                        name="frequency"
                        :options="$frequencies"
                    />
                    <x-forms.error name="frequency" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-forms.label for="nextOccurrenceDate" required>Data da próxima ocorrência</x-forms.label>
                    <x-forms.input wire:model="nextOccurrenceDate" id="nextOccurrenceDate" name="nextOccurrenceDate" type="date" />
                    <p class="text-xs text-foreground-muted mt-1">Este dia será usado como base para os próximos lançamentos.</p>
                    <x-forms.error name="nextOccurrenceDate" />
                </div>
                <div>
                    <x-forms.label for="endDate">Data de término (Opcional)</x-forms.label>
                    <x-forms.input wire:model="endDate" id="endDate" name="endDate" type="date" />
                    <x-forms.error name="endDate" />
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-forms.label for="status" required>Status</x-forms.label>
                    <x-forms.select wire:model="status" id="status" name="status" :options="$statuses" />
                    <x-forms.error name="status" />
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <x-ui.button type="button" variant="ghost" x-on:click="open = false">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary" loading wire:target="save">Salvar</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
