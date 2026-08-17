<div class="p-6">
    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
        Contribuir para: {{ $goal->name }}
    </h2>

    <form wire:submit="save" class="space-y-4">
        <div>
            <label for="account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Conta de Origem</label>
            <select id="account_id" wire:model="account_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">Selecione a conta</option>
                @foreach($this->accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->name }} (Saldo: R$ {{ number_format($account->balance, 2, ',', '.') }})</option>
                @endforeach
            </select>
            @error('account_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Valor</label>
            <input type="number" step="0.01" id="amount" wire:model="amount" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            @error('amount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="contribution_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data</label>
            <input type="date" id="contribution_date" wire:model="contribution_date" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            @error('contribution_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição (Opcional)</label>
            <textarea id="description" wire:model="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
            @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="mt-6 flex justify-end space-x-3">
            <button type="button" wire:click="$dispatch('closeModal')" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Cancelar
            </button>
            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Contribuir
            </button>
        </div>
    </form>
</div>
