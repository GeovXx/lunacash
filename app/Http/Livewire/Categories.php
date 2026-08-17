<?php

namespace App\Http\Livewire;

use App\Models\Category;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Categorias'])]
class Categories extends Component
{
    public ?string $editingId = null;

    public string $name = '';

    public string $type = 'expense';

    public ?string $parentId = null;

    private const TYPES = [
        'expense' => 'Despesa',
        'income' => 'Receita',
        'transfer' => 'Transferência',
        'savings' => 'Poupança',
        'investment' => 'Investimento',
        'other' => 'Outro',
    ];

    public function create(): void
    {
        $this->authorize('create', Category::class);

        $this->resetForm();
        $this->dispatch('open-category-form');
    }

    public function edit(string $id): void
    {
        $category = Category::query()->visibleTo(auth()->user())->findOrFail($id);
        $this->authorize('update', $category);

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->type = $category->type;
        $this->parentId = $category->parent_id;

        $this->dispatch('open-category-form');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'parentId' => [
                'nullable',
                'uuid',
                Rule::exists('categories', 'id')->where(function ($query): void {
                    $query->whereNull('parent_id')
                        ->where(function ($query): void {
                            $query->whereNull('user_id')->orWhere('user_id', auth()->id());
                        });
                }),
                function (string $attribute, $value, \Closure $fail): void {
                    if ($value !== null && $value === $this->editingId) {
                        $fail('Uma categoria não pode ser sua própria categoria pai.');
                    }
                },
            ],
        ]);

        $payload = [
            'name' => $validated['name'],
            'type' => $validated['type'],
            'parent_id' => $validated['parentId'],
        ];

        if ($this->editingId) {
            $category = Category::query()->visibleTo(auth()->user())->findOrFail($this->editingId);
            $this->authorize('update', $category);
            $category->update($payload);
        } else {
            $this->authorize('create', Category::class);
            Category::create($payload);
        }

        $this->dispatch('close-category-form');
        $this->resetForm();
    }

    public function delete(string $id): void
    {
        $category = Category::query()->visibleTo(auth()->user())->findOrFail($id);
        $this->authorize('delete', $category);

        $category->delete();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'parentId']);
        $this->type = 'expense';
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.categories', [
            'categories' => Category::query()->with('parent')->visibleTo($user)->orderBy('name')->get(),
            'parentOptions' => Category::query()->visibleTo($user)->whereNull('parent_id')
                ->when($this->editingId, fn ($query) => $query->where('id', '!=', $this->editingId))
                ->orderBy('name')->get(),
            'types' => self::TYPES,
        ]);
    }
}
