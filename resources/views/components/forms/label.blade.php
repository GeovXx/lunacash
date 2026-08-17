@props(['for' => null, 'required' => false])

<label @if ($for) for="{{ $for }}" @endif {{ $attributes->merge(['class' => 'text-label font-medium text-foreground-secondary']) }}>
    {{ $slot }}
    @if ($required)
        <span class="text-danger">*</span>
    @endif
</label>
