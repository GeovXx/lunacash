<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ isset($title) ? $title.' · '.config('app.name', 'LunaCash') : config('app.name', 'LunaCash') }}</title>
        @include('partials.theme-init')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="bg-background text-foreground antialiased" x-data="{ sidebarOpen: false }">
        <div class="flex min-h-screen">
            @include('layouts.partials.sidebar')

            <div class="flex min-w-0 flex-1 flex-col">
                @include('layouts.partials.topbar', ['title' => $title ?? null])

                <main class="flex-1 px-4 py-8 sm:px-6 lg:px-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
        @livewireScripts
    </body>
</html>
