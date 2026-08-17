<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-semibold text-slate-800 dark:text-white">Contas a Receber</h2>
        <button wire:click="$dispatch('openReceivableForm')" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
            Nova Receita
        </button>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 mb-6 p-4">
        <div class="flex space-x-2">
            <button wire:click="setStatus('open')" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $status === 'open' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                Em Aberto
            </button>
            <button wire:click="setStatus('paid')" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $status === 'paid' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                Recebidas
            </button>
            <button wire:click="setStatus('cancelled')" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $status === 'cancelled' ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                Canceladas
            </button>
            <button wire:click="setStatus('all')" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $status === 'all' ? 'bg-slate-200 text-slate-800 dark:bg-slate-700 dark:text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                Todas
            </button>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 text-sm">
                    <th class="px-6 py-4 font-medium">Título</th>
                    <th class="px-6 py-4 font-medium">Vencimento</th>
                    <th class="px-6 py-4 font-medium">Categoria</th>
                    <th class="px-6 py-4 font-medium">Valor</th>
                    <th class="px-6 py-4 font-medium">Status</th>
                    <th class="px-6 py-4 font-medium text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                @forelse($receivables as $receivable)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-medium text-slate-900 dark:text-white">{{ $receivable->title }}</div>
                            @if($receivable->installmentPlan)
                                <div class="text-xs text-slate-500 mt-1">Parcela {{ $receivable->installment_number }} de {{ $receivable->installmentPlan->installments_count }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-slate-600 dark:text-slate-300">
                            {{ $receivable->due_date->format('d/m/Y') }}
                            @if($receivable->is_overdue)
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400">
                                    Atrasada
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300">
                                {{ $receivable->category->name }}
                            </span>
                        </td>
                        <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">
                            R$ {{ number_format($receivable->amount, 2, ',', '.') }}
                        </td>
                        <td class="px-6 py-4">
                            @if($receivable->status === 'open')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                    Aberto
                                </span>
                            @elseif($receivable->status === 'paid')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                    Recebida
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300">
                                    {{ ucfirst($receivable->status) }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-3">
                            <button wire:click="$dispatch('openReceivableForm', { obligationId: '{{ $receivable->id }}' })" class="text-emerald-600 hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-300 text-sm font-medium transition-colors">
                                Editar
                            </button>
                            @if($receivable->status === 'open')
                                <button wire:click="$dispatch('openReceivableReceiveModal', { obligationId: '{{ $receivable->id }}' })" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium transition-colors">
                                    Receber
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                            Nenhuma conta encontrada nesta situação.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($receivables->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
                {{ $receivables->links() }}
            </div>
        @endif
    </div>

    <livewire:receivables.receivable-form />
    <livewire:receivables.receivable-receive-modal />
</div>
