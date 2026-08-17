<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Metas</h2>
        <button wire:click="$dispatch('openModal', { component: 'goals.goal-form' })" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            Nova Meta
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($this->goals as $goal)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-100 dark:border-gray-700">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <a href="{{ route('goals.show', $goal) }}" class="hover:underline">{{ $goal->name }}</a>
                    </h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $goal->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($goal->status === 'paused' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : ($goal->status === 'cancelled' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200')) }}">
                        {{ ucfirst($goal->status) }}
                    </span>
                </div>

                <div class="mb-2 flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Progresso</span>
                    <span class="font-medium text-gray-900 dark:text-white">
                        R$ {{ number_format($goal->current_amount, 2, ',', '.') }} / R$ {{ number_format($goal->target_amount, 2, ',', '.') }}
                    </span>
                </div>

                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700 mb-4">
                    <div class="bg-indigo-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ $goal->progress_percentage }}%"></div>
                </div>

                @if($goal->target_date)
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Alvo: {{ $goal->target_date->format('d/m/Y') }}
                    </p>
                @endif
                
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end space-x-2">
                    @if($goal->status === 'active')
                        <button wire:click="$dispatch('openModal', { component: 'goals.contribution-form', arguments: { goal: '{{ $goal->id }}' } })" class="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                            Contribuir
                        </button>
                    @endif
                    <button wire:click="$dispatch('openModal', { component: 'goals.goal-form', arguments: { goal: '{{ $goal->id }}' } })" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                        Editar
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-gray-500 dark:text-gray-400">
                Nenhuma meta cadastrada.
            </div>
        @endforelse
    </div>
</div>
