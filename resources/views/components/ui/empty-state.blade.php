@props(['icon' => 'inbox', 'title' => 'Nada por aqui ainda', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-lg border border-dashed border-border p-10 text-center']) }}>
    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-surface-elevated text-foreground-muted">
        <x-dynamic-component :component="'lucide-'.$icon" class="h-6 w-6" />
    </span>
    <p class="mt-4 text-sm font-medium text-foreground">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 text-sm text-foreground-muted">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-4">{{ $action }}</div>
    @endisset
</div>
