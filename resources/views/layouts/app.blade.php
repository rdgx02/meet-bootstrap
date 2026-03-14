<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Meet LADETEC'))</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="app-admin-body">
    <div class="app-admin-shell">
        @include('layouts.navigation')

        <div class="app-main-panel">
            <div class="app-content-shell">
                <main class="app-content">
                    @isset($header)
                        <div class="app-slot-header">
                            {{ $header }}
                        </div>
                    @endisset

                    @yield('content')

                    @isset($slot)
                        {{ $slot }}
                    @endisset
                </main>
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
