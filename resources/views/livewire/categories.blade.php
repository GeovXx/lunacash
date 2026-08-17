<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-h2 font-semibold text-foreground">Categorias</h2>
            <p class="text-sm text-foreground-muted">Organize despesas, receitas e outras classificações financeiras.</p>
        </div>
        <x-ui.button wire:click="create">
            <x-lucide-plus class="h-4 w-4" />
            Nova categoria
        </x-ui.button>
    </div>

    @if ($categories->isEmpty())
        <x-ui.empty-state
            icon="tags"
            title="Nenhuma categoria encontrada"
            description="Crie sua primeira categoria personalizada."
        >
            <x-slot:action>
                <x-ui.button wire:click="create">Criar categoria</x-ui.button>
            </x-slot:action>
        </x-ui.empty-state>
    @else
        <div class="space-y-4">
            @foreach ($categories->groupBy('type') as $type => $group)
                <x-ui.card>
                    <x-slot:header>
                        <h3 class="text-h3 font-semibold text-foreground">{{ $types[$type] ?? ucfirst($type) }}</h3>
                        <x-ui.badge variant="neutral">{{ $group->count() }}</x-ui.badge>
                    </x-slot:header>

                    <ul class="divide-y divide-border">
                        @foreach ($group->sortBy(fn ($category) => $category->parent_id !== null) as $category)
                            <li class="flex items-center justify-between gap-3 py-3" wire:key="category-{{ $category->id }}">
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-foreground">
                                        @if ($category->parent_id)
                                            <span class="text-foreground-muted">{{ $category->parent?->name }} /</span>
                                        @endif
                                        {{ $category->name }}
                                    </p>
                                    @if ($category->user_id === null)
                                        <span class="text-xs text-foreground-muted">Categoria padrão</span>
                                    @endif
                                </div>

                                @if ($category->user_id !== null)
                                    <div class="flex items-center gap-1">
                                        <x-ui.button variant="ghost" size="sm" wire:click="edit('{{ $category->id }}')" aria-label="Editar categoria">
                                            <x-lucide-pencil class="h-4 w-4" />
                                        </x-ui.button>
                                        <x-ui.button
                                            variant="ghost"
                                            size="sm"
                                            wire:click="delete('{{ $category->id }}')"
                                            wire:confirm="Tem certeza que deseja excluir esta categoria?"
                                            aria-label="Excluir categoria"
                                        >
                                            <x-lucide-trash-2 class="h-4 w-4" />
                                        </x-ui.button>
                                    </div>
                                @else
                                    <x-ui.badge variant="neutral">Somente leitura</x-ui.badge>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            @endforeach
        </div>
    @endif

    <x-ui.modal name="category-form" :title="$editingId ? 'Editar categoria' : 'Nova categoria'">
        <form wire:submit="save" class="space-y-4">
            <div>
                <x-forms.label for="name" required>Nome</x-forms.label>
                <x-forms.input wire:model="name" id="name" name="name" placeholder="Ex: Alimentação" />
                <x-forms.error name="name" />
            </div>

            <div>
                <x-forms.label for="type" required>Tipo</x-forms.label>
                <x-forms.select wire:model="type" id="type" name="type" :options="$types" />
                <x-forms.error name="type" />
            </div>

            <div>
                <x-forms.label for="parentId">Categoria pai (opcional)</x-forms.label>
                <x-forms.select
                    wire:model="parentId"
                    id="parentId"
                    name="parentId"
                    placeholder="Nenhuma"
                    :options="$parentOptions->pluck('name', 'id')->toArray()"
                />
                <x-forms.error name="parentId" />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <x-ui.button type="button" variant="ghost" x-on:click="open = false">Cancelar</x-ui.button>
                <x-ui.button type="submit" loading wire:target="save">Salvar</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
