<?php

namespace App\Http\Livewire;

use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringProfile;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Recorrências'])]
class RecurringProfiles extends Component
{
    use WithPagination;

    public ?string $editingId = null;

    public ?string $accountId = null;

    public ?string $categoryId = null;

    public string $name = '';

    public string $type = 'expense';

    public string $frequency = 'monthly';

    public string $amount = '';

    public string $nextOccurrenceDate = '';

    public ?string $endDate = null;

    public string $status = 'active';

    public ?string $description = null;

    private const FREQUENCIES = [
        'daily' => 'Diário',
        'weekly' => 'Semanal',
        'biweekly' => 'Quinzenal',
        'monthly' => 'Mensal',
        'quarterly' => 'Trimestral',
        'semiannually' => 'Semestral',
        'annually' => 'Anual',
    ];

    private const STATUSES = [
        'active' => 'Ativa',
        'paused' => 'Pausada',
        'cancelled' => 'Cancelada',
        'completed' => 'Concluída',
    ];

    public function mount(): void
    {
        // ...
    }

    public function create(): void
    {
        $this->authorize('create', RecurringProfile::class);

        $this->resetForm();
        $this->dispatch('open-recurring-profile-form');
    }

    public function edit(string $id): void
    {
        $profile = RecurringProfile::query()->forUser()->findOrFail($id);
        $this->authorize('update', $profile);

        $this->editingId = $profile->id;
        $this->accountId = $profile->account_id;
        $this->categoryId = $profile->category_id;
        $this->name = $profile->name;
        $this->type = $profile->type;
        $this->frequency = $profile->frequency;
        $this->amount = (string) $profile->amount;
        $this->nextOccurrenceDate = $profile->next_occurrence_date->toDateString();
        $this->endDate = $profile->end_date?->toDateString();
        $this->status = $profile->status;
        $this->description = $profile->description;

        $this->dispatch('open-recurring-profile-form');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'accountId' => ['required', 'uuid', Rule::exists('accounts', 'id')->where('user_id', auth()->id())],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'categoryId' => [
                'required',
                'uuid',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->where('type', $this->type)
                        ->where(function ($q) {
                            $q->where('user_id', auth()->id())->orWhereNull('user_id');
                        });
                }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'frequency' => ['required', Rule::in(array_keys(self::FREQUENCIES))],
            'amount' => ['required', 'numeric', 'gt:0'],
            'nextOccurrenceDate' => ['required', 'date'],
            'endDate' => ['nullable', 'date', 'after:nextOccurrenceDate'],
            'status' => ['required', Rule::in(['active', 'paused', 'cancelled', 'completed'])],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $payload = [
            'account_id' => $validated['accountId'],
            'category_id' => $validated['categoryId'],
            'type' => $validated['type'],
            'name' => $validated['name'],
            'frequency' => $validated['frequency'],
            'amount' => $validated['amount'],
            'currency' => 'BRL',
            'next_occurrence_date' => $validated['nextOccurrenceDate'],
            'end_date' => $validated['endDate'],
            'status' => $validated['status'],
            'description' => $validated['description'],
        ];

        if ($this->editingId) {
            $profile = RecurringProfile::query()->forUser()->findOrFail($this->editingId);
            $this->authorize('update', $profile);

            // If we are changing the next_occurrence_date, we need to update the start_day metadata
            if ($profile->next_occurrence_date->toDateString() !== $validated['nextOccurrenceDate']) {
                $payload['metadata'] = array_merge($profile->metadata ?? [], ['start_day' => (int) date('d', strtotime($validated['nextOccurrenceDate']))]);
            }

            $profile->update($payload);
        } else {
            $this->authorize('create', RecurringProfile::class);
            $payload['metadata'] = ['start_day' => (int) date('d', strtotime($validated['nextOccurrenceDate']))];
            RecurringProfile::create($payload);
        }

        $this->dispatch('close-recurring-profile-form');
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'accountId', 'categoryId', 'name', 'type', 'frequency', 'amount', 'nextOccurrenceDate', 'endDate', 'description']);
        $this->status = 'active';
        $this->type = 'expense';
        $this->frequency = 'monthly';
        $this->nextOccurrenceDate = now()->toDateString();
    }

    public function togglePause(string $id): void
    {
        $profile = RecurringProfile::query()->forUser()->findOrFail($id);
        $this->authorize('update', $profile);

        if ($profile->status === 'active') {
            $profile->update(['status' => 'paused']);
        } elseif ($profile->status === 'paused') {
            $profile->update(['status' => 'active']);
        }
    }

    public function render()
    {
        $profiles = RecurringProfile::query()
            ->forUser()
            ->with(['account', 'category'])
            ->orderBy('next_occurrence_date')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.recurring-profiles', [
            'profiles' => $profiles,
            'accounts' => Account::query()->forUser()->orderBy('name')->get(),
            'categories' => Category::query()
                ->where(function ($q) {
                    $q->where('user_id', auth()->id())->orWhereNull('user_id');
                })
                ->where('type', $this->type)
                ->orderBy('name')
                ->get(),
            'frequencies' => self::FREQUENCIES,
            'statuses' => self::STATUSES,
        ]);
    }
}
