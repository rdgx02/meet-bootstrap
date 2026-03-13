<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Meet LADETEC'))</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <div class="app-shell">
        @include('layouts.navigation')

        @isset($header)
            <header class="border-bottom bg-white bg-opacity-75">
                <div class="container app-container py-4">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main class="app-main">
            <div class="container app-container">
                @yield('content')

                @isset($slot)
                    {{ $slot }}
                @endisset
            </div>
        </main>
    </div>

    @livewireScripts
</body>
</html>
