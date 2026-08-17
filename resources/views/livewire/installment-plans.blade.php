<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900 dark:text-white">Parcelamentos</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Gerencie seus planos de parcelamento, financiamentos e empréstimos.
            </p>
        </div>
        <livewire:installment-plan-form />
    </div>

    @if($viewingPlanId && $viewingPlan)
        <!-- View Detalhes -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-medium text-slate-900 dark:text-white">Detalhes do Parcelamento</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $viewingPlan->title }}</p>
                </div>
                <button wire:click="closeView" class="p-2 text-slate-400 hover:text-slate-500 dark:hover:text-slate-300">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-900/50">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">R$ {{ number_format($viewingPlan->total_amount, 2, ',', '.') }}</p>
                </div>
                <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-900/50">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Parcelas</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">{{ $viewingPlan->installments_count }}</p>
                </div>
                <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-900/50">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Conta</p>
                    <p class="mt-1 text-sm font-medium text-slate-900 dark:text-white truncate">{{ $viewingPlan->account->name }}</p>
                </div>
                <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-900/50">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</p>
                    <p class="mt-1 text-sm font-medium text-slate-900 dark:text-white">
                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium
                            @if($viewingPlan->status === 'completed') bg-emerald-100 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-400
                            @elseif($viewingPlan->status === 'active') bg-blue-100 text-blue-700 dark:bg-blue-400/10 dark:text-blue-400
                            @else bg-slate-100 text-slate-700 dark:bg-slate-400/10 dark:text-slate-400 @endif
                        ">
                            {{ ucfirst($viewingPlan->status) }}
                        </span>
                    </p>
                </div>
            </div>

            <div class="overflow-hidden border border-slate-200 dark:border-slate-700 sm:rounded-lg">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nº</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Vencimento</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Valor</th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status (Obrigação)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach($viewingPlan->obligations as $obs)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white">
                                    {{ $obs->installment_number }} / {{ $viewingPlan->installments_count }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                                    {{ \Carbon\Carbon::parse($obs->due_date)->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium {{ $obs->direction === 'payable' ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                    R$ {{ number_format($obs->amount, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center text-sm">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300">
                                        {{ ucfirst($obs->status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <!-- Lista de Planos -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Título</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tipo</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Valor Total</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                        @forelse($plans as $plan)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                                            <i data-lucide="layers" class="h-5 w-5 text-slate-500 dark:text-slate-400"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-slate-900 dark:text-white">{{ $plan->title }}</div>
                                            <div class="text-sm text-slate-500 dark:text-slate-400">{{ $plan->installments_count }} parcelas</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium {{ $plan->direction === 'payable' ? 'bg-rose-100 text-rose-700 dark:bg-rose-400/10 dark:text-rose-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-400' }}">
                                        {{ $plan->direction === 'payable' ? 'A Pagar' : 'A Receber' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right {{ $plan->direction === 'payable' ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                    R$ {{ number_format($plan->total_amount, 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium
                                        @if($plan->status === 'completed') bg-emerald-100 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-400
                                        @elseif($plan->status === 'active') bg-blue-100 text-blue-700 dark:bg-blue-400/10 dark:text-blue-400
                                        @else bg-slate-100 text-slate-700 dark:bg-slate-400/10 dark:text-slate-400 @endif
                                    ">
                                        {{ ucfirst($plan->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button wire:click="viewPlan('{{ $plan->id }}')" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3">
                                        Visualizar
                                    </button>
                                    @if($plan->status === 'active')
                                        <button wire:click="cancelPlan('{{ $plan->id }}')" wire:confirm="Tem certeza que deseja cancelar? As parcelas geradas serão mantidas." class="text-rose-600 hover:text-rose-900 dark:text-rose-400 dark:hover:text-rose-300">
                                            Cancelar
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-slate-500 dark:text-slate-400">
                                    <div class="flex flex-col items-center">
                                        <i data-lucide="layers" class="h-10 w-10 text-slate-400 mb-3"></i>
                                        <p class="text-base font-medium">Nenhum parcelamento encontrado</p>
                                        <p class="text-sm mt-1">Crie seu primeiro parcelamento clicando no botão acima.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($plans->hasPages())
                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
                    {{ $plans->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
