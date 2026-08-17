@props([])

<label class="inline-flex items-center gap-2 text-sm text-foreground-secondary">
    <input type="checkbox" {{ $attributes->merge(['class' => 'h-4 w-4 rounded border-border text-primary focus:ring-2 focus:ring-primary']) }}>
    {{ $slot }}
</label>
