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
            <div class="pt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                @auth
                    <a href="{{ route('dashboard') }}" class="w-full sm:w-auto px-8 py-3 rounded-full bg-primary text-primary-foreground font-medium shadow-sm hover:bg-primary/90 transition-colors">
                        Acessar Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="w-full sm:w-auto px-8 py-3 rounded-full bg-surface-elevated border border-border text-foreground font-medium shadow-sm hover:bg-surface-elevated/80 transition-colors">
                        Entrar
                    </a>
                    <a href="{{ route('register') }}" class="w-full sm:w-auto px-8 py-3 rounded-full bg-primary text-primary-foreground font-medium shadow-sm hover:bg-primary/90 transition-colors">
                        Criar Conta Livremente
                    </a>
                @endauth
            </div>
        </div>
        @livewireScripts
    </body>
</html>
