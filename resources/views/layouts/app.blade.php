<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'Meet LADETEC'))</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            {{-- Navegação do Breeze (já tem Logout, Profile etc.) --}}
            @include('layouts.navigation')

            {{-- Page Heading (usado por telas do Breeze) --}}
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            {{-- Page Content --}}
            <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {{-- Para páginas do Meet (extends/section) --}}
                @yield('content')

                {{-- Para páginas que usam componente (<x-app-layout>) --}}
                @isset($slot)
                    {{ $slot }}
                @endisset
            </main>
        </div>
    </body>
</html>