<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'LunaCash') }}</title>
        @include('partials.theme-init')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-background text-foreground antialiased flex items-center justify-center px-6">
        <div class="fixed right-4 top-4">
            <x-layout.theme-toggle />
        </div>

        <div class="max-w-2xl text-center space-y-6">
            <div class="flex justify-center">
                <x-ui.logo size="lg" />
            </div>
            <p class="text-display font-semibold tracking-tight text-foreground">Controle financeiro premium</p>
            <p class="text-lg text-foreground-secondary">Sistema de controle financeiro pessoal, moderno e seguro.</p>
            <p class="text-sm text-foreground-muted">Base Laravel 12 + Blade + Livewire 3 + Tailwind CSS + Vite pronta para as próximas etapas.</p>
            @livewire('home-page')
        </div>
        @livewireScripts
    </body>
</html>
