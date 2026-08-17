@props(['variant' => 'info', 'duration' => 4000])

@php
    $variants = [
        'success' => ['bg' => 'bg-success-subtle', 'text' => 'text-success', 'icon' => 'circle-check'],
        'warning' => ['bg' => 'bg-warning-subtle', 'text' => 'text-warning', 'icon' => 'triangle-alert'],
        'danger' => ['bg' => 'bg-danger-subtle', 'text' => 'text-danger', 'icon' => 'circle-x'],
        'info' => ['bg' => 'bg-info-subtle', 'text' => 'text-info', 'icon' => 'info'],
    ];

    $style = $variants[$variant] ?? $variants['info'];
    $icon = 'lucide-'.$style['icon'];
@endphp

{{-- Visual primitive only; a global stacking/dispatch mechanism will be wired in the Layout stage. --}}
<div
    x-data="{ show: true }"
    x-show="show"
    x-init="setTimeout(() => show = false, {{ $duration }})"
    x-transition.opacity.duration.200ms
    {{ $attributes->merge(['class' => 'flex items-center gap-3 rounded-md border border-border p-3 shadow-sm '.$style['bg']]) }}
    role="status"
>
    <x-dynamic-component :component="$icon" class="h-4 w-4 shrink-0 {{ $style['text'] }}" />
    <div class="text-sm {{ $style['text'] }}">{{ $slot }}</div>
    <button type="button" x-on:click="show = false" class="ml-auto text-foreground-muted hover:text-foreground" aria-label="Fechar">
        <x-lucide-x class="h-4 w-4" />
    </button>
</div>
