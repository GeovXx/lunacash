<div>
    <button wire:click="openModal" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
        <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
        Novo Parcelamento
    </button>

    @if($isModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                
                <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity" aria-hidden="true" wire:click="closeModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 dark:border-slate-700">
                    <form wire:submit="save">
                        <div class="px-6 pt-6 pb-4">
                            <div class="flex items-center justify-between mb-5">
                                <h3 class="text-xl font-semibold text-slate-900 dark:text-white" id="modal-title">
                                    Novo Parcelamento
                                </h3>
                                <button type="button" wire:click="closeModal" class="text-slate-400 hover:text-slate-500 dark:hover:text-slate-300">
                                    <i data-lucide="x" class="w-5 h-5"></i>
                                </button>
                            </div>

                            @if($errors->has('general'))
                                <div class="mb-4 p-3 rounded-lg bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400 text-sm">
                                    {{ $errors->first('general') }}
                                </div>
                            @endif

                            <div class="space-y-4">
                                <!-- Direção (Tipo) -->
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tipo de Parcelamento</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <label class="flex items-center p-3 border rounded-lg cursor-pointer transition-colors @if($direction === 'payable') border-rose-500 bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-400 @else border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 @endif">
                                            <input type="radio" wire:model.live="direction" value="payable" class="sr-only">
                                            <i data-lucide="arrow-up-right" class="w-4 h-4 mr-2"></i>
                                            <span class="text-sm font-medium">A Pagar</span>
                                        </label>
                                        <label class="flex items-center p-3 border rounded-lg cursor-pointer transition-colors @if($direction === 'receivable') border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 @else border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 @endif">
                                            <input type="radio" wire:model.live="direction" value="receivable" class="sr-only">
                                            <i data-lucide="arrow-down-left" class="w-4 h-4 mr-2"></i>
                                            <span class="text-sm font-medium">A Receber</span>
                                        </label>
                                    </div>
                                    @error('direction') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                <!-- Titulo e Conta -->
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Título</label>
                                    <input type="text" wire:model="title" class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    @error('title') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Conta {{ $direction === 'payable' ? 'de Origem' : 'de Destino' }}</label>
                                    <select wire:model="account_id" class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="">Selecione a conta</option>
                                        @foreach($accounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('account_id') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                <!-- Categoria e Total -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Categoria</label>
                                        <select wire:model="category_id" class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <option value="">Selecione...</option>
                                            @foreach($categories as $cat)
                                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('category_id') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Valor Total (R$)</label>
                                        <input type="number" step="0.01" wire:model="total_amount" class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        @error('total_amount') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <!-- Parcelas e Frequência -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nº de Parcelas</label>
                                        <input type="number" min="1" step="1" wire:model="installments_count" class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        @error('installments_count') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Frequência</label>
                                        <select wire:model="frequency" class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <option value="weekly">Semanal</option>
                                            <option value="biweekly">Quinzenal</option>
                                            <option value="monthly">Mensal</option>
                                        </select>
                                        @error('frequency') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <!-- Vencimento e Notas -->
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">1º Vencimento</label>
                                    <input type="date" wire:model="first_due_date" class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    @error('first_due_date') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Notas</label>
                                    <textarea wire:model="notes" rows="2" class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                                    @error('notes') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-700 flex justify-end space-x-3 rounded-b-xl">
                            <button type="button" wire:click="closeModal" class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors flex items-center">
                                <span wire:loading.remove wire:target="save">Gerar Parcelas</span>
                                <span wire:loading wire:target="save">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Gerando...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
