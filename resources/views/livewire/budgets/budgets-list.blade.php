<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Orçamentos') }}
            </h2>
            <button wire:click="$dispatch('openBudgetForm')" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                <i data-lucide="plus" class="w-4 h-4 mr-1"></i> {{ __('Novo Orçamento') }}
            </button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                @forelse($budgets as $budget)
                    <div class="p-6 bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $budget->name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $budget->period_start->format('d/m/Y') }} - {{ $budget->period_end->format('d/m/Y') }}
                                </p>
                            </div>
                            <div class="flex space-x-2">
                                <button wire:click="$dispatch('openBudgetForm', { id: '{{ $budget->id }}' })" class="text-gray-400 hover:text-indigo-500">
                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                </button>
                                <button wire:click="deleteBudget('{{ $budget->id }}')" wire:confirm="Tem certeza que deseja excluir este orçamento?" class="text-gray-400 hover:text-red-500">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mt-4">
                            @if(isset($budget->progress['excess_amount']))
                                <div class="px-3 py-2 mb-3 text-sm text-yellow-800 bg-yellow-100 rounded-md dark:bg-yellow-900/30 dark:text-yellow-400">
                                    <i data-lucide="alert-triangle" class="inline w-4 h-4 mr-1"></i> Orçamento superestimado em R$ {{ number_format($budget->progress['excess_amount'], 2, ',', '.') }}
                                </div>
                            @endif

                            <div class="flex justify-between mb-1 text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Realizado</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">
                                    R$ {{ number_format($budget->progress['actual_amount_sum'], 2, ',', '.') }} / 
                                    R$ {{ number_format($budget->progress['target_amount'] > 0 ? $budget->progress['target_amount'] : $budget->progress['planned_amount_sum'], 2, ',', '.') }}
                                </span>
                            </div>
                            
                            @php
                                $percent = $budget->progress['percentage_used'];
                                $color = $percent > 100 ? 'bg-red-500' : ($percent > 80 ? 'bg-yellow-500' : 'bg-green-500');
                            @endphp
                            
                            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                <div class="{{ $color }} h-2.5 rounded-full" style="width: {{ min(100, $percent) }}%"></div>
                            </div>
                            <div class="mt-1 text-xs text-right text-gray-500 dark:text-gray-400">
                                {{ $percent }}%
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('budgets.show', $budget->id) }}" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                                {{ __('Ver detalhes') }} <i data-lucide="arrow-right" class="w-4 h-4 ml-1"></i>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full">
                        <div class="p-12 text-center bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                            <i data-lucide="pie-chart" class="w-12 h-12 mx-auto text-gray-400"></i>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Nenhum orçamento encontrado</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Comece criando um orçamento para controlar seus gastos.</p>
                            <div class="mt-6">
                                <button wire:click="$dispatch('openBudgetForm')" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Criar Orçamento
                                </button>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    @livewire('budgets.budget-form')
</div>
