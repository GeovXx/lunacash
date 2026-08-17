@props(['class' => ''])

{{-- Alpine ships bundled with Livewire's assets, no extra dependency required. --}}
<button
    type="button"
    x-data="{ dark: document.documentElement.classList.contains('dark') }"
    x-on:click="
        dark = !dark;
        document.documentElement.classList.toggle('dark', dark);
        localStorage.setItem('lunacash-theme', dark ? 'dark' : 'light');
    "
    aria-label="Alternar tema"
    {{ $attributes->merge(['class' => 'inline-flex h-9 w-9 items-center justify-center rounded-md border border-border text-foreground-secondary transition hover:text-foreground hover:bg-surface-elevated focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary '.$class]) }}
>
    <x-lucide-sun x-show="dark" x-cloak class="h-4 w-4" />
    <x-lucide-moon x-show="!dark" x-cloak class="h-4 w-4" />
</button>
