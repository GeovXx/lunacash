@props(['name'])

@error($name)
    <p {{ $attributes->merge(['class' => 'mt-1.5 flex items-center gap-1 text-sm text-danger']) }}>
        <x-lucide-circle-alert class="h-3.5 w-3.5 shrink-0" />
        {{ $message }}
    </p>
@enderror
