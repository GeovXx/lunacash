@props(['title' => null])

<header class="sticky top-0 z-20 flex items-center justify-between gap-4 border-b border-border bg-surface/80 px-4 py-3 backdrop-blur sm:px-6 lg:px-8">
    <div class="flex items-center gap-3">
        <button
            type="button"
            x-on:click="sidebarOpen = true"
            class="inline-flex h-9 w-9 items-center justify-center rounded-md text-foreground-secondary hover:bg-surface-elevated hover:text-foreground md:hidden"
            aria-label="Abrir menu"
        >
            <x-lucide-menu class="h-5 w-5" />
        </button>

        <h1 class="text-h3 font-semibold text-foreground">{{ $title ?? 'Dashboard' }}</h1>
    </div>

    <div class="flex items-center gap-2">
        <!-- Global Search Button -->
        <button
            type="button"
            x-data
            x-on:click="$dispatch('keydown', { key: 'k', ctrlKey: true, metaKey: true })"
            class="hidden sm:flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium text-foreground-secondary transition hover:bg-surface-elevated hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary border border-border"
            title="Pesquisar (Ctrl+K)"
        >
            <x-lucide-search class="h-4 w-4" />
            <span class="text-xs text-foreground-muted">Pesquisar...</span>
            <kbd class="hidden lg:inline-flex items-center gap-1 rounded bg-surface px-1.5 text-[10px] font-medium text-foreground-muted border border-border ml-4">
                <span class="text-xs">Ctrl K</span>
            </kbd>
        </button>
        <button
            type="button"
            x-data
            x-on:click="$dispatch('keydown', { key: 'k', ctrlKey: true, metaKey: true })"
            class="sm:hidden flex items-center justify-center h-9 w-9 rounded-md text-foreground-secondary transition hover:bg-surface-elevated hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
            aria-label="Pesquisar"
        >
            <x-lucide-search class="h-5 w-5" />
        </button>

        <livewire:notification-bell />
        <x-layout.theme-toggle />

        <x-ui.dropdown align="right">
            <x-slot:trigger>
                <button
                    type="button"
                    class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm font-medium text-foreground-secondary transition hover:bg-surface-elevated hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                >
                    <x-ui.avatar :name="auth()->user()->name" size="sm" />
                    <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                    <x-lucide-chevron-down class="h-4 w-4" />
                </button>
            </x-slot:trigger>

            <div class="px-3 py-2 text-sm">
                <p class="font-medium text-foreground">{{ auth()->user()->name }}</p>
                <p class="truncate text-foreground-muted">{{ auth()->user()->email }}</p>
            </div>
            <x-ui.divider />
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm text-danger transition hover:bg-danger-subtle"
                >
                    <x-lucide-log-out class="h-4 w-4" />
                    Sair
                </button>
            </form>
        </x-ui.dropdown>
    </div>

    <!-- Inject Global Search Component -->
    <livewire:global-search />
</header>

