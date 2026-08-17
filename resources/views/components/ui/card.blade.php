@props(['padded' => true])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-border bg-surface shadow-xs '.($padded ? 'p-6' : '')]) }}>
    @isset($header)
        <div class="mb-4 flex items-center justify-between">
            {{ $header }}
        </div>
    @endisset

    {{ $slot }}

    @isset($footer)
        <div class="mt-4 border-t border-border pt-4">
            {{ $footer }}
        </div>
    @endisset
</div>
