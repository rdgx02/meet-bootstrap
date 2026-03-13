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
            <header class="app-topbar">
                <div class="app-topbar-start">
                    <button
                        class="btn app-sidebar-toggle d-lg-none"
                        type="button"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#appSidebar"
                        aria-controls="appSidebar"
                        aria-label="Abrir menu"
                    >
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>

                    <div class="app-topbar-meta">
                        <span class="app-topbar-label">Painel administrativo</span>
                        <strong class="app-topbar-title">@yield('title', config('app.name', 'Meet LADETEC'))</strong>
                    </div>
                </div>

                <div class="dropdown">
                    <button
                        class="btn app-user-toggle dropdown-toggle d-flex align-items-center gap-2"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >
                        <span class="app-avatar app-avatar-soft">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                        <span class="text-start d-none d-md-block">
                            <span class="d-block fw-semibold">{{ Auth::user()->name }}</span>
                            <small class="d-block text-body-secondary">{{ Auth::user()->email }}</small>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">Sair</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </header>

            <div class="app-content-shell">
                <main class="app-content">
                    @isset($header)
                        <div class="app-slot-header app-card p-3 p-md-4 mb-3">
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
