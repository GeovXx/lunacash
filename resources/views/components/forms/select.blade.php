@props(['error' => null, 'options' => [], 'placeholder' => null])

@php
    $hasError = $error || $errors->has($attributes->get('name') ?? '');

    $classes = 'block w-full rounded-md border bg-surface px-3 text-sm text-foreground shadow-xs transition focus:outline-none focus:ring-2 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-50 h-10 '
        .($hasError ? 'border-danger focus:ring-danger' : 'border-border focus:border-primary');
@endphp

<select {{ $attributes->merge(['class' => $classes]) }}>
    @if ($placeholder)
        <option value="" disabled selected>{{ $placeholder }}</option>
    @endif

    @foreach ($options as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach

    {{ $slot }}
</select>
