@props(['name' => '', 'src' => null, 'size' => 'md'])

@php
    $sizes = [
        'sm' => 'h-7 w-7 text-xs',
        'md' => 'h-10 w-10 text-sm',
        'lg' => 'h-14 w-14 text-base',
    ];

    $initials = collect(explode(' ', trim($name)))
        ->filter()
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

@if ($src)
    <img src="{{ $src }}" alt="{{ $name }}" {{ $attributes->merge(['class' => 'rounded-full object-cover '.($sizes[$size] ?? $sizes['md'])]) }}>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-full bg-primary/10 font-medium text-primary '.($sizes[$size] ?? $sizes['md'])]) }}>
        {{ $initials ?: '?' }}
    </span>
@endif
