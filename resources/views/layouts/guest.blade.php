<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Meet LADETEC') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-auth-shell">
        <div class="app-auth-card">
            <div class="text-center mb-4">
                <a href="/" class="text-decoration-none text-dark">
                    <span class="app-brand-mark mx-auto mb-3">M</span>
                    <h1 class="h3 mb-1">Meet LADETEC</h1>
                    <p class="text-body-secondary mb-0">Agenda de salas com Laravel + Bootstrap</p>
                </a>
            </div>

            <div class="app-card p-4 p-md-5">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
