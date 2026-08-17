<?php

namespace App\Http\Livewire;

use App\Models\Account;
use App\Models\AccountType;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Contas'])]
class Accounts extends Component
{
    public ?string $editingId = null;

    public string $name = '';

    public ?string $institution = null;

    public ?string $accountTypeId = null;

    public ?string $accountNumber = null;

    public string $status = 'active';

    public function create(): void
    {
        $this->authorize('create', Account::class);

        $this->resetForm();
        $this->dispatch('open-account-form');
    }

    public function edit(string $id): void
    {
        $account = Account::query()->forUser()->findOrFail($id);
        $this->authorize('update', $account);

        $this->editingId = $account->id;
        $this->name = $account->name;
        $this->institution = $account->institution;
        $this->accountTypeId = $account->account_type_id;
        $this->accountNumber = $account->account_number;
        $this->status = $account->status;

        $this->dispatch('open-account-form');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'institution' => ['nullable', 'string', 'max:255'],
            'accountTypeId' => ['required', 'uuid', Rule::exists('account_types', 'id')],
            'accountNumber' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'closed', 'archived'])],
        ]);

        $payload = [
            'name' => $validated['name'],
            'institution' => $validated['institution'],
            'account_type_id' => $validated['accountTypeId'],
            'account_number' => $validated['accountNumber'],
            'status' => $validated['status'],
        ];

        if ($this->editingId) {
            $account = Account::query()->forUser()->findOrFail($this->editingId);
            $this->authorize('update', $account);
            $account->update($payload);
        } else {
            $this->authorize('create', Account::class);
            Account::create($payload);
        }

        $this->dispatch('close-account-form');
        $this->resetForm();
    }

    public function delete(string $id): void
    {
        $account = Account::query()->forUser()->findOrFail($id);
        $this->authorize('delete', $account);

        $account->delete();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'institution', 'accountNumber']);
        $this->status = 'active';
        $this->accountTypeId = AccountType::query()->orderBy('name')->value('id');
    }

    public function render()
    {
        return view('livewire.accounts', [
            'accounts' => Account::query()->forUser()->with('accountType')->orderBy('name')->get(),
            'accountTypes' => AccountType::query()->orderBy('name')->get(),
        ]);
    }
}
