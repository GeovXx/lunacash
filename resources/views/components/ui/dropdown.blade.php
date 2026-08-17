@props(['align' => 'right'])

@php
    $alignment = $align === 'left' ? 'left-0' : 'right-0';
@endphp

<div x-data="{ open: false }" x-on:keydown.escape.window="open = false" class="relative inline-block">
    <div x-on:click="open = !open">
        {{ $trigger }}
    </div>

    <div
        x-show="open"
        x-cloak
        x-on:click.outside="open = false"
        x-transition.opacity.scale.duration.100ms
        class="absolute {{ $alignment }} z-20 mt-2 w-56 rounded-md border border-border bg-surface p-1 shadow-md"
    >
        {{ $slot }}
    </div>
</div>
