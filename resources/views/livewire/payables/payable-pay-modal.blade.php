<div>
@if($isOpen)
<div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-slate-900/50 dark:bg-slate-900/80 backdrop-blur-sm transition-opacity" wire:click="$set('isOpen', false)"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal panel -->
        <div class="relative inline-block align-bottom bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full border border-slate-200 dark:border-slate-700">
            <div class="px-6 py-6 border-b border-slate-100 dark:border-slate-700/50">
                <h3 class="text-xl font-semibold text-slate-800 dark:text-white" id="modal-title">
                    Pagar Conta
                </h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 font-medium">
                    {{ $title }}
                </p>
            </div>

            <form wire:submit.prevent="pay">
                <div class="px-6 py-6 space-y-5">
                    @if($errors->has('general'))
                        <div class="p-3 bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 rounded-lg text-sm font-medium border border-rose-200 dark:border-rose-800/50">
                            {{ $errors->first('general') }}
                        </div>
                    @endif

                    <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-100 dark:border-emerald-800/30 mb-4">
                        <p class="text-sm text-emerald-800 dark:text-emerald-400 font-medium text-center">Valor a Pagar</p>
                        <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-500 text-center mt-1">
                            R$ {{ number_format($expected_amount, 2, ',', '.') }}
                        </p>
                    </div>

                    <!-- Amount -->
                    <div>
                        <label for="pay_amount" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Confirme o Valor (R$)</label>
                        <input type="number" step="0.01" id="pay_amount" wire:model="amount" 
                               class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                        @error('amount') <span class="mt-1 text-sm text-rose-500">{{ $message }}</span> @enderror
                        <p class="mt-1 text-xs text-slate-500">O pagamento parcial não é suportado no momento. O valor deve ser exato.</p>
                    </div>

                    <!-- Date -->
                    <div>
                        <label for="transaction_date" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Data do Pagamento</label>
                        <input type="date" id="transaction_date" wire:model="transaction_date" 
                               class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                        @error('transaction_date') <span class="mt-1 text-sm text-rose-500">{{ $message }}</span> @enderror
                    </div>

                    <!-- Account -->
                    <div>
                        <label for="pay_account_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Conta de Origem (Pagadora)</label>
                        <select id="pay_account_id" wire:model="account_id" 
                                class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }} (Saldo: R$ {{ number_format($account->balance, 2, ',', '.') }})</option>
                            @endforeach
                        </select>
                        @error('account_id') <span class="mt-1 text-sm text-rose-500">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-700/50 flex items-center justify-end space-x-3">
                    <button type="button" wire:click="$set('isOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                        Confirmar Pagamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
</div>
