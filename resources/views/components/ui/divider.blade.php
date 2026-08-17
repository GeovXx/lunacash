@props(['label' => null])

@if ($label)
    <div {{ $attributes->merge(['class' => 'flex items-center gap-3 text-xs text-foreground-muted']) }}>
        <span class="h-px flex-1 bg-border"></span>
        {{ $label }}
        <span class="h-px flex-1 bg-border"></span>
    </div>
@else
    <hr {{ $attributes->merge(['class' => 'border-border']) }}>
@endif
