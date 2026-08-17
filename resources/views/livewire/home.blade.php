<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-foreground">Visão Geral</h1>
            <p class="text-sm text-foreground-secondary">
                Acompanhe o seu resumo financeiro de {{ ucfirst($currentMonth->translatedFormat('F Y')) }}.
            </p>
        </div>
    </div>

    {{-- Seção 1: Resumo Financeiro (KPIs) --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Saldo em Contas --}}
        <x-ui.card class="p-6">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <x-lucide-wallet class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-sm font-medium text-foreground-secondary">Saldo em Contas</p>
                    <p class="text-xl font-bold text-foreground">R$ {{ number_format($accountsBalance, 2, ',', '.') }}</p>
                </div>
            </div>
        </x-ui.card>

        {{-- Receitas do Mês --}}
        <x-ui.card class="p-6">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-success-subtle text-success">
                    <x-lucide-arrow-up-circle class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-sm font-medium text-foreground-secondary">Receitas (Mês)</p>
                    <p class="text-xl font-bold text-success">R$ {{ number_format($monthlyIncomes, 2, ',', '.') }}</p>
                </div>
            </div>
        </x-ui.card>

        {{-- Despesas do Mês --}}
        <x-ui.card class="p-6">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-destructive-subtle text-destructive">
                    <x-lucide-arrow-down-circle class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-sm font-medium text-foreground-secondary">Despesas (Mês)</p>
                    <p class="text-xl font-bold text-destructive">R$ {{ number_format($monthlyExpenses, 2, ',', '.') }}</p>
                </div>
            </div>
        </x-ui.card>

        {{-- Faturas em Aberto --}}
        <x-ui.card class="p-6">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-500/10 text-orange-600 dark:text-orange-400">
                    <x-lucide-credit-card class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-sm font-medium text-foreground-secondary">Faturas Pendentes</p>
                    <p class="text-xl font-bold text-foreground">R$ {{ number_format($pendingInvoicesTotal, 2, ',', '.') }}</p>
                </div>
            </div>
        </x-ui.card>
    </div>

    {{-- Seção 1.5: Gráficos --}}
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Fluxo de Caixa Mensal --}}
        <x-ui.card class="p-6">
            <h2 class="text-base font-semibold text-foreground mb-4">Fluxo de Caixa (Últimos 6 Meses)</h2>
            <div 
                x-data="{
                    chart: null,
                    init() {
                        if (typeof window.ApexCharts !== 'undefined') {
                            this.renderChart();
                        } else {
                            setTimeout(() => this.renderChart(), 500);
                        }
                    },
                    renderChart() {
                        const options = {
                            series: [
                                { name: 'Receitas', data: {{ json_encode($cashFlowChart['incomes']) }} },
                                { name: 'Despesas', data: {{ json_encode($cashFlowChart['expenses']) }} }
                            ],
                            chart: { type: 'bar', height: 300, toolbar: { show: false }, background: 'transparent' },
                            plotOptions: { bar: { horizontal: false, columnWidth: '55%', borderRadius: 2 } },
                            dataLabels: { enabled: false },
                            stroke: { show: true, width: 2, colors: ['transparent'] },
                            xaxis: { categories: {!! json_encode($cashFlowChart['labels']) !!}, axisBorder: { show: false }, axisTicks: { show: false } },
                            yaxis: { labels: { formatter: (value) => 'R$ ' + value.toFixed(2) } },
                            fill: { opacity: 1 },
                            colors: ['#10b981', '#ef4444'], // success, destructive
                            theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
                            tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light', y: { formatter: function (val) { return 'R$ ' + val.toFixed(2) } } }
                        };
                        this.chart = new window.ApexCharts(this.$refs.cashFlowChart, options);
                        this.chart.render();
                    }
                }"
            >
                <div x-ref="cashFlowChart"></div>
            </div>
        </x-ui.card>

        {{-- Despesas por Categoria --}}
        <x-ui.card class="p-6">
            <h2 class="text-base font-semibold text-foreground mb-4">Gastos por Categoria ({{ ucfirst($currentMonth->translatedFormat('F')) }})</h2>
            <div 
                x-data="{
                    chart: null,
                    init() {
                        if (typeof window.ApexCharts !== 'undefined') {
                            this.renderChart();
                        } else {
                            setTimeout(() => this.renderChart(), 500);
                        }
                    },
                    renderChart() {
                        const series = {{ json_encode($expensesByCategoryChart['series']) }};
                        if (series.length === 0) {
                            this.$refs.categoryChart.innerHTML = '<div class=\'flex h-[300px] items-center justify-center text-sm text-foreground-secondary\'>Nenhum gasto registrado neste mês.</div>';
                            return;
                        }
                        const options = {
                            series: series,
                            labels: {!! json_encode($expensesByCategoryChart['labels']) !!},
                            chart: { type: 'donut', height: 300, background: 'transparent' },
                            dataLabels: { enabled: false },
                            stroke: { show: false },
                            theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
                            tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light', y: { formatter: function (val) { return 'R$ ' + val.toFixed(2) } } },
                            legend: { position: 'bottom' }
                        };
                        this.chart = new window.ApexCharts(this.$refs.categoryChart, options);
                        this.chart.render();
                    }
                }"
            >
                <div x-ref="categoryChart"></div>
            </div>
        </x-ui.card>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        
        {{-- Coluna 1 e 2: Contas, Orçamentos e Metas --}}
        <div class="space-y-6 lg:col-span-2">
            
            {{-- Minhas Contas --}}
            <x-ui.card>
                <div class="border-b border-border px-6 py-4">
                    <h2 class="text-base font-semibold text-foreground">Minhas Contas</h2>
                </div>
                <div class="divide-y divide-border">
                    @forelse($activeAccounts as $account)
                        <div class="flex items-center justify-between px-6 py-3">
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-foreground">{{ $account->name }}</span>
                                <span class="text-xs text-foreground-secondary">{{ $account->institution }}</span>
                            </div>
                            <span class="text-sm font-semibold {{ $account->balance >= 0 ? 'text-success' : 'text-destructive' }}">
                                R$ {{ number_format($account->balance, 2, ',', '.') }}
                            </span>
                        </div>
                    @empty
                        <div class="px-6 py-6 text-center text-sm text-foreground-secondary">
                            Nenhuma conta cadastrada.
                        </div>
                    @endforelse
                </div>
            </x-ui.card>

            <div class="grid gap-6 md:grid-cols-2">
                {{-- Orçamentos --}}
                <x-ui.card>
                    <div class="flex items-center justify-between border-b border-border px-6 py-4">
                        <h2 class="text-base font-semibold text-foreground">Orçamentos Ativos</h2>
                        <a href="{{ route('budgets.index') }}" wire:navigate class="text-xs font-medium text-primary hover:underline">Ver todos</a>
                    </div>
                    <div class="divide-y divide-border">
                        @forelse($budgets as $budget)
                            <div class="px-6 py-4">
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="text-sm font-medium text-foreground">{{ $budget['name'] }}</span>
                                    <span class="text-sm font-semibold text-foreground">{{ $budget['percentage_used'] }}%</span>
                                </div>
                                <div class="mb-1 h-2 w-full overflow-hidden rounded-full bg-surface-elevated">
                                    <div class="h-full {{ $budget['percentage_used'] > 100 ? 'bg-destructive' : ($budget['percentage_used'] > 80 ? 'bg-orange-500' : 'bg-primary') }}" style="width: {{ min($budget['percentage_used'], 100) }}%"></div>
                                </div>
                                <div class="flex justify-between text-xs text-foreground-secondary">
                                    <span>R$ {{ number_format($budget['actual_amount'], 2, ',', '.') }} gastos</span>
                                    <span>de R$ {{ number_format($budget['target_amount'], 2, ',', '.') }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-6 text-center text-sm text-foreground-secondary">
                                Nenhum orçamento ativo para este mês.
                            </div>
                        @endforelse
                    </div>
                </x-ui.card>

                {{-- Metas --}}
                <x-ui.card>
                    <div class="flex items-center justify-between border-b border-border px-6 py-4">
                        <h2 class="text-base font-semibold text-foreground">Metas Financeiras</h2>
                        <a href="{{ route('goals.index') }}" wire:navigate class="text-xs font-medium text-primary hover:underline">Ver todas</a>
                    </div>
                    <div class="divide-y divide-border">
                        @forelse($goals as $goal)
                            <div class="px-6 py-4">
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="text-sm font-medium text-foreground">{{ $goal['name'] }}</span>
                                    <span class="text-sm font-semibold text-foreground">{{ $goal['percentage'] }}%</span>
                                </div>
                                <div class="mb-1 h-2 w-full overflow-hidden rounded-full bg-surface-elevated">
                                    <div class="h-full bg-success" style="width: {{ $goal['percentage'] }}%"></div>
                                </div>
                                <div class="flex justify-between text-xs text-foreground-secondary">
                                    <span>R$ {{ number_format($goal['current_amount'], 2, ',', '.') }} salvos</span>
                                    <span>de R$ {{ number_format($goal['target_amount'], 2, ',', '.') }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-6 text-center text-sm text-foreground-secondary">
                                Nenhuma meta ativa no momento.
                            </div>
                        @endforelse
                    </div>
                </x-ui.card>
            </div>
            
        </div>

        {{-- Coluna 3: Próximos Eventos --}}
        <div class="space-y-6">
            <x-ui.card>
                <div class="flex items-center justify-between border-b border-border px-6 py-4">
                    <h2 class="text-base font-semibold text-foreground">Próximos 15 dias</h2>
                    <a href="{{ route('calendar') }}" wire:navigate class="text-xs font-medium text-primary hover:underline">Calendário</a>
                </div>
                <div class="divide-y divide-border">
                    @forelse(collect($upcomingEvents)->take(7) as $event)
                        @php
                            $isReceivable = $event['direction'] === 'receivable';
                            $isNeutral = $event['direction'] === 'neutral';
                        @endphp
                        <div class="flex items-center justify-between px-6 py-3 hover:bg-surface-elevated/50">
                            <div class="flex items-center gap-3 truncate">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $isNeutral ? 'bg-blue-500/10 text-blue-500' : ($isReceivable ? 'bg-emerald-500/10 text-emerald-500' : 'bg-red-500/10 text-red-500') }}">
                                    @if($event['source_type'] === 'transaction')
                                        <x-lucide-list class="h-4 w-4" />
                                    @elseif($event['source_type'] === 'obligation')
                                        <x-lucide-receipt class="h-4 w-4" />
                                    @elseif($event['source_type'] === 'invoice')
                                        <x-lucide-credit-card class="h-4 w-4" />
                                    @elseif($event['source_type'] === 'goal')
                                        <x-lucide-target class="h-4 w-4" />
                                    @else
                                        <x-lucide-repeat class="h-4 w-4" />
                                    @endif
                                </div>
                                <div class="flex flex-col truncate">
                                    <span class="truncate text-sm font-medium text-foreground">{{ $event['title'] }}</span>
                                    <span class="text-xs text-foreground-secondary">{{ \Carbon\Carbon::parse($event['date'])->format('d/m') }} - {{ ucfirst($event['status']) }}</span>
                                </div>
                            </div>
                            <span class="shrink-0 text-sm font-bold {{ $isNeutral ? 'text-blue-600 dark:text-blue-400' : ($isReceivable ? 'text-emerald-600 dark:text-emerald-400' : 'text-foreground') }}">
                                {{ $isReceivable && !$isNeutral ? '+' : ($isNeutral ? '' : '-') }} R$ {{ number_format($event['amount'], 2, ',', '.') }}
                            </span>
                        </div>
                    @empty
                        <div class="px-6 py-6 text-center text-sm text-foreground-secondary">
                            Nenhum evento agendado para os próximos 15 dias.
                        </div>
                    @endforelse
                </div>
            </x-ui.card>
        </div>
        
    </div>
</div>
