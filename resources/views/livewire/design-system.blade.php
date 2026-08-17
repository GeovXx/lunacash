<div class="mx-auto max-w-5xl space-y-12">
    <div>
        <p class="text-label font-medium uppercase tracking-wide text-foreground-muted">LunaCash</p>
        <h1 class="text-display font-semibold text-foreground">Design System</h1>
        <p class="mt-2 max-w-2xl text-foreground-secondary">
            Base visual reutilizável: cores, tipografia, espaçamento e componentes. Esta página é apenas
            uma vitrine interna para validação — não é uma tela financeira.
        </p>
    </div>

    {{-- Typography --}}
    <section>
        <h2 class="text-h3 font-semibold text-foreground">Tipografia</h2>
        <div class="mt-4 space-y-3 rounded-lg border border-border bg-surface p-6">
            <p class="text-display font-semibold text-foreground">Display</p>
            <p class="text-h1 font-semibold text-foreground">Heading 1</p>
            <p class="text-h2 font-semibold text-foreground">Heading 2</p>
            <p class="text-h3 font-semibold text-foreground">Heading 3</p>
            <p class="text-base text-foreground">Body — texto padrão de parágrafos e conteúdo.</p>
            <p class="text-sm text-foreground-secondary">Small — texto secundário.</p>
            <p class="text-caption text-foreground-muted">Caption — legendas e metadados.</p>
            <p class="text-label font-medium uppercase tracking-wide text-foreground-muted">Label</p>
        </div>
    </section>

    {{-- Colors --}}
    <section>
        <h2 class="text-h3 font-semibold text-foreground">Cores</h2>
        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ([
                ['label' => 'background', 'class' => 'bg-background border border-border'],
                ['label' => 'surface', 'class' => 'bg-surface border border-border'],
                ['label' => 'surface-elevated', 'class' => 'bg-surface-elevated border border-border'],
                ['label' => 'border', 'class' => 'bg-border'],
                ['label' => 'primary', 'class' => 'bg-primary'],
                ['label' => 'success', 'class' => 'bg-success'],
                ['label' => 'warning', 'class' => 'bg-warning'],
                ['label' => 'danger', 'class' => 'bg-danger'],
                ['label' => 'info', 'class' => 'bg-info'],
            ] as $swatch)
                <div class="overflow-hidden rounded-md border border-border">
                    <div class="h-16 {{ $swatch['class'] }}"></div>
                    <p class="px-2 py-1.5 text-xs text-foreground-secondary">{{ $swatch['label'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Buttons --}}
    <section>
        <h2 class="text-h3 font-semibold text-foreground">Botões</h2>
        <div class="mt-4 flex flex-wrap items-center gap-3 rounded-lg border border-border bg-surface p-6">
            <x-ui.button variant="primary">Primary</x-ui.button>
            <x-ui.button variant="secondary">Secondary</x-ui.button>
            <x-ui.button variant="ghost">Ghost</x-ui.button>
            <x-ui.button variant="outline">Outline</x-ui.button>
            <x-ui.button variant="success">Success</x-ui.button>
            <x-ui.button variant="danger">Danger</x-ui.button>
            <x-ui.button variant="primary" disabled>Disabled</x-ui.button>
        </div>
        <div class="mt-3 flex flex-wrap items-center gap-3 rounded-lg border border-border bg-surface p-6">
            <x-ui.button variant="primary" size="sm">Small</x-ui.button>
            <x-ui.button variant="primary" size="md">Medium</x-ui.button>
            <x-ui.button variant="primary" size="lg">Large</x-ui.button>
        </div>
    </section>

    {{-- Badges & Alerts --}}
    <section>
        <h2 class="text-h3 font-semibold text-foreground">Badges &amp; Alerts</h2>
        <div class="mt-4 flex flex-wrap gap-2">
            <x-ui.badge variant="neutral">Neutro</x-ui.badge>
            <x-ui.badge variant="success">Receita</x-ui.badge>
            <x-ui.badge variant="warning">Pendente</x-ui.badge>
            <x-ui.badge variant="danger">Despesa</x-ui.badge>
            <x-ui.badge variant="info">Info</x-ui.badge>
        </div>
        <div class="mt-4 space-y-3">
            <x-ui.alert variant="success" title="Operação concluída">Tudo certo por aqui.</x-ui.alert>
            <x-ui.alert variant="warning" title="Atenção">Revise os dados antes de continuar.</x-ui.alert>
            <x-ui.alert variant="danger" title="Erro">Algo não saiu como esperado.</x-ui.alert>
            <x-ui.alert variant="info" title="Informação">Apenas um aviso informativo.</x-ui.alert>
        </div>
    </section>

    {{-- Cards --}}
    <section>
        <h2 class="text-h3 font-semibold text-foreground">Cards</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <x-ui.card>
                <x-slot:header>
                    <p class="text-sm font-medium text-foreground-secondary">Saldo (exemplo)</p>
                    <x-ui.badge variant="success">Estável</x-ui.badge>
                </x-slot:header>
                <p class="text-h1 font-semibold text-foreground">R$ 0,00</p>
                <p class="mt-1 text-sm text-foreground-muted">Card reutilizável — sem dados financeiros reais nesta etapa.</p>
            </x-ui.card>

            <x-ui.card>
                <div class="flex items-center gap-3">
                    <x-ui.avatar name="Ana Teste" />
                    <div>
                        <p class="text-sm font-medium text-foreground">Ana Teste</p>
                        <p class="text-xs text-foreground-muted">ana@example.com</p>
                    </div>
                </div>
                <x-ui.divider class="my-4" />
                <p class="text-sm text-foreground-secondary">Exemplo de card de configurações/perfil.</p>
            </x-ui.card>
        </div>
    </section>

    {{-- Feedback states --}}
    <section>
        <h2 class="text-h3 font-semibold text-foreground">Estados de feedback</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <x-ui.card>
                <p class="mb-3 text-sm font-medium text-foreground-secondary">Loading</p>
                <x-ui.loading label="Carregando..." />
                <p class="text-xs text-foreground-muted">(Visível apenas durante requisições Livewire; oculto fora delas.)</p>
            </x-ui.card>

            <x-ui.card>
                <p class="mb-3 text-sm font-medium text-foreground-secondary">Skeleton</p>
                <x-ui.skeleton :lines="3" />
            </x-ui.card>
        </div>

        <div class="mt-4">
            <x-ui.empty-state icon="inbox" title="Nenhum registro" description="Quando houver dados, eles aparecerão aqui.">
                <x-slot:action>
                    <x-ui.button variant="secondary" size="sm">Ação</x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        </div>

        <div class="mt-4">
            <x-ui.toast variant="success">Alteração salva com sucesso.</x-ui.toast>
        </div>
    </section>

    {{-- Overlays --}}
    <section>
        <h2 class="text-h3 font-semibold text-foreground">Tooltip, Dropdown &amp; Modal</h2>
        <div class="mt-4 flex flex-wrap items-center gap-6 rounded-lg border border-border bg-surface p-6">
            <x-ui.tooltip text="Essa é uma dica contextual">
                <x-ui.button variant="outline" size="sm">Hover para tooltip</x-ui.button>
            </x-ui.tooltip>

            <x-ui.dropdown>
                <x-slot:trigger>
                    <x-ui.button variant="outline" size="sm">Abrir menu</x-ui.button>
                </x-slot:trigger>
                <a href="#" class="block rounded-md px-3 py-2 text-sm text-foreground-secondary hover:bg-surface-elevated">Opção 1</a>
                <a href="#" class="block rounded-md px-3 py-2 text-sm text-foreground-secondary hover:bg-surface-elevated">Opção 2</a>
            </x-ui.dropdown>

            <x-ui.button variant="outline" size="sm" x-data x-on:click="$dispatch('open-demo')">Abrir modal</x-ui.button>
        </div>

        <x-ui.modal name="demo" title="Modal de exemplo" max-width="sm">
            <p class="text-sm text-foreground-secondary">Este é um primitivo de modal reutilizável, controlado via Alpine (já incluso pelo Livewire).</p>
            <div class="mt-4 flex justify-end gap-2">
                <x-ui.button variant="ghost" size="sm" x-on:click="$dispatch('close-demo')">Fechar</x-ui.button>
            </div>
        </x-ui.modal>
    </section>

    {{-- Forms --}}
    <section>
        <h2 class="text-h3 font-semibold text-foreground">Formulário</h2>
        <div class="mt-4 max-w-lg space-y-4 rounded-lg border border-border bg-surface p-6">
            <div>
                <x-forms.label for="ds-name">Nome</x-forms.label>
                <x-forms.input id="ds-name" type="text" placeholder="Ex: Ana Teste" class="mt-1.5" />
            </div>

            <div>
                <x-forms.label for="ds-type">Tipo</x-forms.label>
                <x-forms.select id="ds-type" class="mt-1.5" placeholder="Selecione" :options="['expense' => 'Despesa', 'income' => 'Receita']" />
            </div>

            <div>
                <x-forms.label for="ds-notes">Observações</x-forms.label>
                <x-forms.textarea id="ds-notes" placeholder="Opcional" class="mt-1.5" />
            </div>

            <div class="flex items-center gap-4">
                <x-forms.checkbox>Aceito os termos</x-forms.checkbox>
                <x-forms.radio name="ds-radio">Opção A</x-forms.radio>
            </div>

            <div>
                <x-forms.label for="ds-error">Campo com erro (exemplo)</x-forms.label>
                <x-forms.input id="ds-error" type="text" error class="mt-1.5" />
                <p class="mt-1.5 flex items-center gap-1 text-sm text-danger">
                    <x-lucide-circle-alert class="h-3.5 w-3.5 shrink-0" />
                    Este campo é obrigatório.
                </p>
            </div>
        </div>
    </section>

    {{-- Icons --}}
    <section>
        <h2 class="text-h3 font-semibold text-foreground">Ícones (Lucide)</h2>
        <div class="mt-4 flex flex-wrap gap-4 rounded-lg border border-border bg-surface p-6 text-foreground-secondary">
            <x-lucide-wallet class="h-5 w-5" />
            <x-lucide-credit-card class="h-5 w-5" />
            <x-lucide-landmark class="h-5 w-5" />
            <x-lucide-arrow-up-right class="h-5 w-5 text-success" />
            <x-lucide-arrow-down-right class="h-5 w-5 text-danger" />
            <x-lucide-bell class="h-5 w-5" />
            <x-lucide-settings class="h-5 w-5" />
            <x-lucide-search class="h-5 w-5" />
            <x-lucide-moon class="h-5 w-5" />
            <x-lucide-sun class="h-5 w-5" />
        </div>
    </section>
</div>
