@php
    $navigation = [
        ['label' => 'Dashboard', 'route' => 'home', 'icon' => 'layout-dashboard'],
        ['label' => 'Contas', 'route' => 'accounts', 'icon' => 'wallet'],
        ['label' => 'Categorias', 'route' => 'categories', 'icon' => 'tags'],
        ['label' => 'Lançamentos', 'route' => 'transactions', 'icon' => 'list'],
        ['label' => 'Transferências', 'route' => 'transfers', 'icon' => 'arrow-right-left'],
        ['label' => 'Recorrências', 'route' => 'recurring-profiles', 'icon' => 'repeat'],
        ['label' => 'Calendário', 'route' => 'calendar', 'icon' => 'calendar'],
        ['label' => 'Relatórios', 'route' => 'reports.index', 'icon' => 'pie-chart'],
        ['label' => 'Receitas', 'route' => 'incomes', 'icon' => 'arrow-up-circle'],
        ['label' => 'Despesas', 'route' => 'expenses', 'icon' => 'arrow-down-circle'],
        ['label' => 'Design System', 'route' => 'design-system', 'icon' => 'palette'],
    ];
@endphp

{{-- Mobile backdrop --}}
<div
    x-show="sidebarOpen"
    x-cloak
    x-on:click="sidebarOpen = false"
    x-transition.opacity
    class="fixed inset-0 z-30 bg-black/40 md:hidden"
></div>

<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-border bg-surface px-4 py-6 transition-transform duration-200 md:static md:z-auto md:translate-x-0"
>
    <div class="flex items-center justify-between">
        <x-ui.logo />
        <button
            type="button"
            x-on:click="sidebarOpen = false"
            class="inline-flex h-8 w-8 items-center justify-center rounded-md text-foreground-secondary hover:bg-surface-elevated md:hidden"
            aria-label="Fechar menu"
        >
            <x-lucide-x class="h-4 w-4" />
        </button>
    </div>

    <nav class="mt-8 flex flex-1 flex-col gap-1">
        @foreach ($navigation as $item)
            @php $active = request()->routeIs($item['route']); @endphp
            <a
                href="{{ route($item['route']) }}"
                wire:navigate
                class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ $active ? 'bg-primary/10 text-primary' : 'text-foreground-secondary hover:bg-surface-elevated hover:text-foreground' }}"
            >
                <x-dynamic-component :component="'lucide-'.$item['icon']" class="h-4 w-4" />
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>
</aside>
