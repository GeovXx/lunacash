@props(['size' => 'md'])

@php
    $sizes = [
        'sm' => ['icon' => 'h-4 w-4', 'text' => 'text-sm'],
        'md' => ['icon' => 'h-5 w-5', 'text' => 'text-lg'],
        'lg' => ['icon' => 'h-6 w-6', 'text' => 'text-xl'],
    ];

    $style = $sizes[$size] ?? $sizes['md'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 font-semibold text-foreground']) }}>
    <x-lucide-moon class="{{ $style['icon'] }} text-primary" />
    <span class="{{ $style['text'] }}">LunaCash</span>
</span>
