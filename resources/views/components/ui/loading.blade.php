@props(['target' => null, 'label' => 'Carregando...'])

<div
    @if ($target) wire:loading wire:target="{{ $target }}" @else wire:loading @endif
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-sm text-foreground-muted']) }}
>
    <x-lucide-loader-circle class="h-4 w-4 animate-spin" />
    {{ $label }}
</div>
