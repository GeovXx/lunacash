<div>
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
        <div>
            <div class="flex items-center space-x-3">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $goal->name }}</h2>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $goal->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($goal->status === 'paused' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : ($goal->status === 'cancelled' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200')) }}">
                    {{ ucfirst($goal->status) }}
                </span>
            </div>
            @if($goal->description)
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $goal->description }}</p>
            @endif
        </div>
        <div class="flex space-x-3">
            <button wire:click="$dispatch('openModal', { component: 'goals.goal-form', arguments: { goal: '{{ $goal->id }}' } })" class="px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Editar
            </button>
            @if($goal->status === 'active')
                <button wire:click="$dispatch('openModal', { component: 'goals.contribution-form', arguments: { goal: '{{ $goal->id }}' } })" class="px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Contribuir
                </button>
            @endif
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-8 border border-gray-100 dark:border-gray-700">
        <div class="mb-2 flex justify-between text-sm">
            <span class="text-gray-500 dark:text-gray-400">Progresso Geral</span>
            <span class="font-medium text-gray-900 dark:text-white">
                R$ {{ number_format($goal->current_amount, 2, ',', '.') }} / R$ {{ number_format($goal->target_amount, 2, ',', '.') }} ({{ $goal->progress_percentage }}%)
            </span>
        </div>

        <div class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700 mb-4">
            <div class="bg-indigo-600 h-4 rounded-full transition-all duration-500" style="width: {{ $goal->progress_percentage }}%"></div>
        </div>
    </div>

    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Histórico de Contribuições</h3>
    
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-md border border-gray-100 dark:border-gray-700">
        <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($contributions as $contribution)
                <li>
                    <div class="px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400 truncate">
                                + R$ {{ number_format($contribution->amount, 2, ',', '.') }}
                            </p>
                            <div class="ml-2 flex-shrink-0 flex">
                                <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    {{ $contribution->contribution_date->format('d/m/Y') }}
                                </p>
                            </div>
                        </div>
                        <div class="mt-2 sm:flex sm:justify-between">
                            <div class="sm:flex">
                                <p class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                    Conta: {{ $contribution->account->name }}
                                </p>
                            </div>
                            @if($contribution->description)
                                <div class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400 sm:mt-0">
                                    {{ $contribution->description }}
                                </div>
                            @endif
                        </div>
                    </div>
                </li>
            @empty
                <li>
                    <div class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400 sm:px-6">
                        Nenhuma contribuição realizada ainda.
                    </div>
                </li>
            @endforelse
        </ul>
    </div>
</div>
