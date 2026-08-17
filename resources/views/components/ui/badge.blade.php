@props(['variant' => 'neutral'])

@php
    $variants = [
        'neutral' => 'bg-surface-elevated text-foreground-secondary border border-border',
        'success' => 'bg-success-subtle text-success',
        'warning' => 'bg-warning-subtle text-warning',
        'danger' => 'bg-danger-subtle text-danger',
        'info' => 'bg-info-subtle text-info',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium '.($variants[$variant] ?? $variants['neutral'])]) }}>
    {{ $slot }}
</span>
