@props(['variant' => 'info', 'title' => null])

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

<div {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-md border border-border p-4 '.$style['bg']]) }} role="alert">
    <x-dynamic-component :component="$icon" class="mt-0.5 h-5 w-5 shrink-0 {{ $style['text'] }}" />
    <div class="text-sm {{ $style['text'] }}">
        @if ($title)
            <p class="font-medium">{{ $title }}</p>
        @endif
        <div class="{{ $title ? 'mt-1 text-foreground-secondary' : '' }}">
            {{ $slot }}
        </div>
    </div>
</div>
