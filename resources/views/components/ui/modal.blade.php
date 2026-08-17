@props(['name' => 'modal', 'title' => null, 'maxWidth' => 'md'])

@php
    $widths = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
    ];
@endphp

<div
    x-data="{ open: false }"
    x-on:open-{{ $name }}.window="open = true"
    x-on:close-{{ $name }}.window="open = false"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center px-4"
>
    <div x-show="open" x-transition.opacity.duration.150ms class="absolute inset-0 bg-foreground/40" x-on:click="open = false"></div>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        class="relative w-full {{ $widths[$maxWidth] ?? $widths['md'] }} rounded-lg border border-border bg-surface p-6 shadow-md max-h-[90vh] overflow-y-auto"
        role="dialog"
        aria-modal="true"
    >
        @if ($title)
            <h2 class="text-h3 font-semibold text-foreground">{{ $title }}</h2>
        @endif

        <div class="{{ $title ? 'mt-4' : '' }}">
            {{ $slot }}
        </div>
    </div>
</div>
