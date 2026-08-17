<div>
    {{-- Cabeçalho da Página --}}
    <header class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-foreground">Relatórios</h1>
            <p class="text-sm text-foreground-secondary">Análise detalhada de fluxo de caixa, consumo e patrimônio.</p>
        </div>

        @if($activeTab !== 'balances')
        <div class="flex items-center gap-2">
            <button wire:click="previousMonth" class="rounded-md border border-input bg-background p-2 text-foreground-secondary hover:bg-accent hover:text-foreground">
                <i data-lucide="chevron-left" class="h-4 w-4"></i>
            </button>
            <div class="flex h-10 min-w-[140px] items-center justify-center rounded-md border border-input bg-background px-4 text-sm font-medium text-foreground">
                {{ $monthName }}
            </div>
            <button wire:click="nextMonth" class="rounded-md border border-input bg-background p-2 text-foreground-secondary hover:bg-accent hover:text-foreground">
                <i data-lucide="chevron-right" class="h-4 w-4"></i>
            </button>
        </div>
        @endif
    </header>

    {{-- Navegação de Abas e Ações --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-border">
        <div class="flex gap-2">
            <button wire:click="setTab('cash_flow')" class="pb-2 px-4 text-sm font-medium border-b-2 {{ $activeTab === 'cash_flow' ? 'border-primary text-foreground' : 'border-transparent text-foreground-secondary hover:text-foreground hover:border-border' }}">
                Fluxo de Caixa
            </button>
            <button wire:click="setTab('category')" class="pb-2 px-4 text-sm font-medium border-b-2 {{ $activeTab === 'category' ? 'border-primary text-foreground' : 'border-transparent text-foreground-secondary hover:text-foreground hover:border-border' }}">
                Consumo por Categoria
            </button>
            <button wire:click="setTab('balances')" class="pb-2 px-4 text-sm font-medium border-b-2 {{ $activeTab === 'balances' ? 'border-primary text-foreground' : 'border-transparent text-foreground-secondary hover:text-foreground hover:border-border' }}">
                Posição Atual
            </button>
        </div>

        <div class="pb-2">
            @php
                $startExport = \Carbon\Carbon::parse($currentMonth.'-01')->startOfMonth()->toDateString();
                $endExport = \Carbon\Carbon::parse($currentMonth.'-01')->endOfMonth()->toDateString();
            @endphp

            @if($activeTab === 'cash_flow')
                <a href="{{ route('export.cash-flow', ['start' => $startExport, 'end' => $endExport]) }}" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-md border border-input bg-background px-3 py-1.5 text-sm font-medium text-foreground hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <i data-lucide="download" class="h-4 w-4"></i> Exportar CSV
                </a>
            @elseif($activeTab === 'category')
                <a href="{{ route('export.categories', ['start' => $startExport, 'end' => $endExport]) }}" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-md border border-input bg-background px-3 py-1.5 text-sm font-medium text-foreground hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <i data-lucide="download" class="h-4 w-4"></i> Exportar CSV
                </a>
            @elseif($activeTab === 'balances')
                <a href="{{ route('export.balances') }}" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-md border border-input bg-background px-3 py-1.5 text-sm font-medium text-foreground hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <i data-lucide="download" class="h-4 w-4"></i> Exportar CSV
                </a>
            @endif
        </div>
    </div>

    {{-- Conteúdo das Abas --}}
    <div class="space-y-6">
        @if($activeTab === 'cash_flow')
            {{-- Resumo do Fluxo de Caixa --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-ui.card class="p-4">
                    <p class="text-sm font-medium text-foreground-secondary">Entradas (Receitas)</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-500">R$ {{ number_format((float)($cashFlowReport['summary']['income'] ?? 0), 2, ',', '.') }}</p>
                </x-ui.card>
                <x-ui.card class="p-4">
                    <p class="text-sm font-medium text-foreground-secondary">Saídas (Despesas)</p>
                    <p class="mt-2 text-2xl font-bold text-red-600 dark:text-red-500">R$ {{ number_format((float)($cashFlowReport['summary']['expense'] ?? 0), 2, ',', '.') }}</p>
                </x-ui.card>
                <x-ui.card class="p-4">
                    <p class="text-sm font-medium text-foreground-secondary">Pagamentos de Fatura</p>
                    <p class="mt-2 text-2xl font-bold text-red-600 dark:text-red-500">R$ {{ number_format((float)($cashFlowReport['summary']['payment'] ?? 0), 2, ',', '.') }}</p>
                </x-ui.card>
                <x-ui.card class="p-4">
                    <p class="text-sm font-medium text-foreground-secondary">Balanço Líquido</p>
                    <p class="mt-2 text-2xl font-bold {{ (float)($cashFlowReport['summary']['net_flow'] ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-500' : 'text-red-600 dark:text-red-500' }}">
                        R$ {{ number_format((float)($cashFlowReport['summary']['net_flow'] ?? 0), 2, ',', '.') }}
                    </p>
                </x-ui.card>
            </div>

            {{-- Tabela de Fluxo de Caixa --}}
            <x-ui.card>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-foreground">
                        <thead class="border-b border-border bg-accent/50 text-foreground-secondary">
                            <tr>
                                <th class="px-2 sm:px-4 py-3 font-medium">Data</th>
                                <th class="px-2 sm:px-4 py-3 font-medium">Descrição</th>
                                <th class="px-2 sm:px-4 py-3 font-medium">Categoria</th>
                                <th class="px-2 sm:px-4 py-3 font-medium">Conta</th>
                                <th class="px-2 sm:px-4 py-3 font-medium">Tipo</th>
                                <th class="px-2 sm:px-4 py-3 font-medium text-right">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($cashFlowReport['transactions'] ?? [] as $tx)
                            <tr class="hover:bg-accent/50">
                                <td class="px-2 sm:px-4 py-3 whitespace-nowrap">{{ $tx->transaction_date->format('d/m/Y') }}</td>
                                <td class="px-2 sm:px-4 py-3 min-w-[120px]">{{ $tx->description }}</td>
                                <td class="px-2 sm:px-4 py-3 text-foreground-secondary">{{ $tx->category ? $tx->category->name : '-' }}</td>
                                <td class="px-2 sm:px-4 py-3 text-foreground-secondary">{{ $tx->account->name }}</td>
                                <td class="px-2 sm:px-4 py-3">
                                    @if($tx->type === 'income')
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">Receita</span>
                                    @elseif($tx->type === 'expense')
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/30 dark:text-red-400">Despesa</span>
                                    @elseif($tx->type === 'payment')
                                        <span class="inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">Pagamento</span>
                                    @elseif($tx->type === 'transfer')
                                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">Transferência</span>
                                    @endif
                                </td>
                                <td class="px-2 sm:px-4 py-3 text-right font-medium whitespace-nowrap {{ in_array($tx->type, ['expense', 'payment']) ? 'text-red-600 dark:text-red-500' : ($tx->type === 'income' ? 'text-emerald-600 dark:text-emerald-500' : 'text-foreground') }}">
                                    {{ in_array($tx->type, ['expense', 'payment']) ? '-' : '' }}R$ {{ number_format($tx->amount, 2, ',', '.') }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-foreground-secondary">
                                    Nenhum movimento registrado neste período.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if(isset($cashFlowReport['transactions']) && $cashFlowReport['transactions']->hasPages())
                <div class="border-t border-border p-4">
                    {{ $cashFlowReport['transactions']->links() }}
                </div>
                @endif
            </x-ui.card>

        @elseif($activeTab === 'category')
            <div class="grid gap-6 md:grid-cols-4">
                <div class="md:col-span-1">
                    <x-ui.card class="p-6">
                        <h2 class="text-sm font-medium text-foreground-secondary">Consumo Total</h2>
                        <p class="mt-2 text-3xl font-bold text-red-600 dark:text-red-500">R$ {{ number_format((float)($categoryReport['total_consumo'] ?? 0), 2, ',', '.') }}</p>
                        <p class="mt-2 text-xs text-foreground-secondary">Soma de todas as despesas avulsas e compras de cartão de crédito no período selecionado.</p>
                    </x-ui.card>
                </div>
                <div class="md:col-span-3">
                    <x-ui.card>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm text-foreground">
                                <thead class="border-b border-border bg-accent/50 text-foreground-secondary">
                                    <tr>
                                        <th class="px-2 sm:px-4 py-3 font-medium">Categoria</th>
                                        <th class="px-2 sm:px-4 py-3 font-medium text-right">Valor Consumido</th>
                                        <th class="px-2 sm:px-4 py-3 font-medium text-right">% do Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    @forelse($categoryReport['categories'] ?? [] as $cat)
                                    @php
                                        $percent = $categoryReport['total_consumo'] > 0 ? ((float)$cat['amount'] / (float)$categoryReport['total_consumo']) * 100 : 0;
                                    @endphp
                                    <tr class="hover:bg-accent/50">
                                        <td class="px-2 sm:px-4 py-3 font-medium whitespace-nowrap">{{ $cat['name'] }}</td>
                                        <td class="px-2 sm:px-4 py-3 text-right whitespace-nowrap">R$ {{ number_format((float)$cat['amount'], 2, ',', '.') }}</td>
                                        <td class="px-2 sm:px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <span class="text-foreground-secondary min-w-[3rem]">{{ number_format($percent, 1, ',', '.') }}%</span>
                                                <div class="h-2 w-16 overflow-hidden rounded-full bg-accent hidden sm:block">
                                                    <div class="h-full bg-primary" style="width: {{ $percent }}%"></div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-8 text-center text-foreground-secondary">
                                            Nenhum consumo registrado neste mês.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </x-ui.card>
                </div>
            </div>

        @elseif($activeTab === 'balances')
            <div class="grid gap-4 sm:grid-cols-3 mb-6">
                <x-ui.card class="p-4">
                    <p class="text-sm font-medium text-foreground-secondary">Saldos em Contas</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-500">R$ {{ number_format((float)($balancesReport['total_balance'] ?? 0), 2, ',', '.') }}</p>
                </x-ui.card>
                <x-ui.card class="p-4">
                    <p class="text-sm font-medium text-foreground-secondary">Faturas Pendentes</p>
                    <p class="mt-2 text-2xl font-bold text-red-600 dark:text-red-500">R$ {{ number_format((float)($balancesReport['total_debt'] ?? 0), 2, ',', '.') }}</p>
                </x-ui.card>
                <x-ui.card class="p-4">
                    <p class="text-sm font-medium text-foreground-secondary">Balanço Posição (Patrimônio Líquido Parcial)</p>
                    <p class="mt-2 text-2xl font-bold {{ (float)($balancesReport['net_worth'] ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-500' : 'text-red-600 dark:text-red-500' }}">
                        R$ {{ number_format((float)($balancesReport['net_worth'] ?? 0), 2, ',', '.') }}
                    </p>
                </x-ui.card>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <x-ui.card>
                    <div class="border-b border-border p-4">
                        <h3 class="font-medium">Posição das Contas Correntes</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-foreground">
                            <thead class="border-b border-border bg-accent/50 text-foreground-secondary">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Conta</th>
                                    <th class="px-4 py-3 font-medium text-right">Saldo Atual</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @forelse($balancesReport['accounts'] ?? [] as $acc)
                                <tr class="hover:bg-accent/50">
                                    <td class="px-4 py-3">{{ $acc->name }}</td>
                                    <td class="px-4 py-3 text-right font-medium {{ $acc->balance >= 0 ? 'text-emerald-600 dark:text-emerald-500' : 'text-red-600 dark:text-red-500' }}">
                                        R$ {{ number_format($acc->balance, 2, ',', '.') }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="px-4 py-8 text-center text-foreground-secondary">
                                        Nenhuma conta ativa encontrada.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <div class="border-b border-border p-4">
                        <h3 class="font-medium">Posição das Faturas em Aberto</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-foreground">
                            <thead class="border-b border-border bg-accent/50 text-foreground-secondary">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Cartão / Vencimento</th>
                                    <th class="px-4 py-3 font-medium text-right">Saldo Devedor Restante</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @forelse($balancesReport['pending_invoices'] ?? [] as $inv)
                                <tr class="hover:bg-accent/50">
                                    <td class="px-4 py-3">
                                        <div class="font-medium">{{ $inv['card_name'] }}</div>
                                        <div class="text-xs text-foreground-secondary">Vence em: {{ $inv['due_date']->format('d/m/Y') }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-red-600 dark:text-red-500">
                                        R$ {{ number_format((float)$inv['pending_amount'], 2, ',', '.') }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="px-4 py-8 text-center text-foreground-secondary">
                                        Nenhuma fatura pendente.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-ui.card>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            document.addEventListener('livewire:navigated', () => {
                if (typeof window.lucide !== 'undefined') {
                    window.lucide.createIcons();
                }
            });
        </script>
    @endpush
</div>
