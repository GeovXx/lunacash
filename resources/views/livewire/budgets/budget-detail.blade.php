<x-slot name="header">
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <a href="{{ route('budgets.index') }}" class="mr-4 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ $budget->name }}
            </h2>
        </div>
        <button wire:click="$dispatch('openBudgetLineForm')" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
            <i data-lucide="plus" class="w-4 h-4 mr-1"></i> {{ __('Adicionar Categoria') }}
        </button>
    </div>
</x-slot>

<div class="py-12">
    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        
        <!-- Global Progress Card -->
        <div class="p-6 mb-6 bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Progresso Global</h3>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $budget->period_start->format('d/m/Y') }} - {{ $budget->period_end->format('d/m/Y') }}
                </span>
            </div>

            @if(isset($progress['excess_amount']))
                <div class="px-4 py-3 mb-4 border border-yellow-200 rounded-md bg-yellow-50 dark:bg-yellow-900/30 dark:border-yellow-800/50">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-yellow-400"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Orçamento Superestimado</h3>
                            <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-400">
                                <p>Os gastos já superaram o limite definido em <strong>R$ {{ number_format($progress['excess_amount'], 2, ',', '.') }}</strong>.</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Planejado</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">R$ {{ number_format($progress['target_amount'] > 0 ? $progress['target_amount'] : $progress['planned_amount_sum'], 2, ',', '.') }}</div>
                </div>
                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Gasto</div>
                    <div class="text-2xl font-bold {{ $progress['percentage_used'] > 100 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }}">
                        R$ {{ number_format($progress['actual_amount_sum'], 2, ',', '.') }}
                    </div>
                </div>
                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Consumido</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $progress['percentage_used'] }}%</div>
                </div>
            </div>

            <div class="mt-6">
                @php
                    $percent = $progress['percentage_used'];
                    $color = $percent > 100 ? 'bg-red-500' : ($percent > 80 ? 'bg-yellow-500' : 'bg-green-500');
                @endphp
                <div class="w-full h-4 bg-gray-200 rounded-full dark:bg-gray-700">
                    <div class="{{ $color }} h-4 rounded-full transition-all duration-500" style="width: {{ min(100, $percent) }}%"></div>
                </div>
            </div>
        </div>

        <!-- Budget Lines -->
        <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">Categorias</h3>
        
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            @forelse($progress['lines'] as $line)
                <div class="p-6 bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-10 h-10 mr-3 text-white bg-indigo-500 rounded-full">
                                <i data-lucide="tag" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ $line['category_name'] }}</h4>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <button wire:click="$dispatch('openBudgetLineForm', { id: '{{ $line['id'] }}' })" class="text-gray-400 hover:text-indigo-500">
                                <i data-lucide="edit-2" class="w-4 h-4"></i>
                            </button>
                            <button wire:click="deleteBudgetLine('{{ $line['id'] }}')" wire:confirm="Tem certeza que deseja remover esta categoria?" class="text-gray-400 hover:text-red-500">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mt-5">
                        <div class="flex justify-between mb-1 text-sm">
                            <span class="text-gray-600 dark:text-gray-400">R$ {{ number_format($line['actual_amount'], 2, ',', '.') }}</span>
                            <span class="text-gray-600 dark:text-gray-400">Planejado: R$ {{ number_format($line['planned_amount'], 2, ',', '.') }}</span>
                        </div>
                        
                        @php
                            $linePercent = $line['percentage_used'];
                            $lineColor = $linePercent > 100 ? 'bg-red-500' : ($linePercent > 80 ? 'bg-yellow-500' : 'bg-indigo-500');
                        @endphp
                        
                        <div class="w-full h-2 bg-gray-200 rounded-full dark:bg-gray-700">
                            <div class="{{ $lineColor }} h-2 rounded-full" style="width: {{ min(100, $linePercent) }}%"></div>
                        </div>
                        
                        <div class="flex justify-between mt-2 text-xs">
                            <span class="{{ $linePercent > 100 ? 'text-red-500' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ $linePercent }}% utilizado
                            </span>
                            <span class="{{ $linePercent > 100 ? 'text-red-500 font-medium' : 'text-gray-500 dark:text-gray-400' }}">
                                @if($linePercent > 100)
                                    Excedeu R$ {{ number_format($line['actual_amount'] - $line['planned_amount'], 2, ',', '.') }}
                                @else
                                    Resta R$ {{ number_format($line['remaining_amount'], 2, ',', '.') }}
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <div class="p-12 text-center bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <i data-lucide="layers" class="w-12 h-12 mx-auto text-gray-400"></i>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Nenhuma categoria configurada</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Adicione categorias para definir limites de gastos neste orçamento.</p>
                        <div class="mt-6">
                            <button wire:click="$dispatch('openBudgetLineForm')" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700">
                                <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Adicionar Categoria
                            </button>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

    </div>

    @livewire('budgets.budget-line-form', ['budgetId' => $budget->id])
</div>
