@props(['text' => ''])

<span x-data="{ show: false }" class="relative inline-flex" x-on:mouseenter="show = true" x-on:mouseleave="show = false">
    <span x-on:focus="show = true" x-on:blur="show = false" tabindex="0" class="inline-flex">
        {{ $slot }}
    </span>

    <span
        x-show="show"
        x-cloak
        x-transition.opacity.duration.150ms
        role="tooltip"
        class="absolute bottom-full left-1/2 mb-2 -translate-x-1/2 whitespace-nowrap rounded-md bg-foreground px-2.5 py-1.5 text-xs font-medium text-background shadow-sm"
    >
        {{ $text }}
    </span>
</span>
