@props(['lines' => 3])

<div {{ $attributes->merge(['class' => 'animate-pulse space-y-2']) }}>
    @for ($i = 0; $i < $lines; $i++)
        <div class="h-3 rounded bg-surface-elevated" style="width: {{ [100, 90, 75][$i % 3] }}%"></div>
    @endfor
</div>
