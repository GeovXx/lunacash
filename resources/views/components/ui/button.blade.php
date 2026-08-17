@props([
    'variant' => 'primary',
    'size' => 'md',
    'as' => 'button',
    'type' => 'submit',
    'loading' => false,
])

@php
    $variants = [
        'primary' => 'bg-primary text-primary-foreground hover:bg-primary-hover focus-visible:ring-primary',
        'secondary' => 'bg-surface-elevated text-foreground border border-border hover:bg-surface focus-visible:ring-primary',
        'ghost' => 'bg-transparent text-foreground-secondary hover:text-foreground hover:bg-surface-elevated focus-visible:ring-primary',
        'outline' => 'bg-transparent border border-border text-foreground hover:bg-surface-elevated focus-visible:ring-primary',
        'danger' => 'bg-danger text-white hover:opacity-90 focus-visible:ring-danger',
        'success' => 'bg-success text-white hover:opacity-90 focus-visible:ring-success',
    ];

    $sizes = [
        'sm' => 'h-8 px-3 text-sm gap-1.5',
        'md' => 'h-10 px-4 text-sm gap-2',
        'lg' => 'h-12 px-6 text-base gap-2',
    ];

    $classes = 'inline-flex items-center justify-center rounded-md font-medium transition disabled:opacity-50 disabled:pointer-events-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-background '
        .($variants[$variant] ?? $variants['primary']).' '
        .($sizes[$size] ?? $sizes['md']);
@endphp

<{{ $as }}
    @if ($as === 'button') type="{{ $type }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
    @if ($loading) wire:loading.attr="disabled" @endif
>
    @if ($loading)
        <x-lucide-loader-circle class="h-4 w-4 animate-spin" wire:loading wire:target="{{ $attributes->get('wire:target') ?? '' }}" />
    @endif
    {{ $slot }}
</{{ $as }}>
