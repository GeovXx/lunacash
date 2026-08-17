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
    <body class="min-h-screen bg-background text-foreground antialiased flex items-center justify-center px-4 py-10 sm:px-6">
        <div class="fixed right-4 top-4">
            <x-layout.theme-toggle />
        </div>

        <div class="w-full max-w-md">
            <div class="mb-8 flex justify-center">
                <x-ui.logo size="lg" />
            </div>

            {{ $slot }}
        </div>
        @livewireScripts
    </body>
</html>
