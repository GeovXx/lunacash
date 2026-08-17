<div>
@if($isOpen)
<div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-slate-900/50 dark:bg-slate-900/80 backdrop-blur-sm transition-opacity" wire:click="$set('isOpen', false)"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal panel -->
        <div class="relative inline-block align-bottom bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-slate-200 dark:border-slate-700">
            <div class="px-6 py-6 border-b border-slate-100 dark:border-slate-700/50">
                <h3 class="text-xl font-semibold text-slate-800 dark:text-white" id="modal-title">
                    {{ $obligationId ? 'Editar Receita' : 'Nova Receita' }}
                </h3>
                @if($isInstallment)
                <p class="mt-1 text-sm text-amber-600 dark:text-amber-400 font-medium">
                    Esta conta pertence a um parcelamento. Apenas anotações podem ser editadas.
                </p>
                @endif
            </div>

            <form wire:submit.prevent="save">
                <div class="px-6 py-6 space-y-5">
                    @if($errors->has('general'))
                        <div class="p-3 bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 rounded-lg text-sm font-medium border border-rose-200 dark:border-rose-800/50">
                            {{ $errors->first('general') }}
                        </div>
                    @endif

                    <!-- Title -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Título da Conta</label>
                        <input type="text" id="title" wire:model="title" 
                               class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm {{ ($isInstallment || $isPaidOrCancelled) ? 'opacity-60 cursor-not-allowed' : '' }}" 
                               {{ ($isInstallment || $isPaidOrCancelled) ? 'disabled' : '' }}
                               placeholder="Ex: Salário">
                        @error('title') <span class="mt-1 text-sm text-rose-500">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Amount -->
                        <div>
                            <label for="amount" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Valor (R$)</label>
                            <input type="number" step="0.01" id="amount" wire:model="amount" 
                                   class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm {{ ($isInstallment || $isPaidOrCancelled) ? 'opacity-60 cursor-not-allowed' : '' }}"
                                   {{ ($isInstallment || $isPaidOrCancelled) ? 'disabled' : '' }}>
                            @error('amount') <span class="mt-1 text-sm text-rose-500">{{ $message }}</span> @enderror
                        </div>

                        <!-- Due Date -->
                        <div>
                            <label for="due_date" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Data de Vencimento</label>
                            <input type="date" id="due_date" wire:model="due_date" 
                                   class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm {{ ($isInstallment || $isPaidOrCancelled) ? 'opacity-60 cursor-not-allowed' : '' }}"
                                   {{ ($isInstallment || $isPaidOrCancelled) ? 'disabled' : '' }}>
                            @error('due_date') <span class="mt-1 text-sm text-rose-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Category -->
                        <div>
                            <label for="category_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Categoria (Receita)</label>
                            <select id="category_id" wire:model="category_id" 
                                    class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm {{ ($isInstallment || $isPaidOrCancelled) ? 'opacity-60 cursor-not-allowed' : '' }}"
                                    {{ ($isInstallment || $isPaidOrCancelled) ? 'disabled' : '' }}>
                                <option value="">Selecione...</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <span class="mt-1 text-sm text-rose-500">{{ $message }}</span> @enderror
                        </div>

                        <!-- Account -->
                        <div>
                            <label for="account_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Conta Prevista</label>
                            <select id="account_id" wire:model="account_id" 
                                    class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm {{ ($isInstallment || $isPaidOrCancelled) ? 'opacity-60 cursor-not-allowed' : '' }}"
                                    {{ ($isInstallment || $isPaidOrCancelled) ? 'disabled' : '' }}>
                                <option value="">Selecione...</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                            @error('account_id') <span class="mt-1 text-sm text-rose-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Anotações</label>
                        <textarea id="notes" wire:model="notes" rows="2" 
                                  class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"></textarea>
                        @error('notes') <span class="mt-1 text-sm text-rose-500">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-700/50 flex items-center justify-end space-x-3">
                    <button type="button" wire:click="$set('isOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
</div>
