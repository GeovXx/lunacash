@props(['error' => null, 'rows' => 4])

@php
    $hasError = $error || $errors->has($attributes->get('name') ?? '');

    $classes = 'block w-full rounded-md border bg-surface px-3 py-2 text-sm text-foreground shadow-xs transition placeholder:text-foreground-muted focus:outline-none focus:ring-2 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-50 '
        .($hasError ? 'border-danger focus:ring-danger' : 'border-border focus:border-primary');
@endphp

<textarea rows="{{ $rows }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</textarea>
