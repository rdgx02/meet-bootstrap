@php
    $usersRoute = \Illuminate\Support\Facades\Route::has('users.index') ? route('users.index') : null;
    $canViewRooms = auth()->user()?->can('viewAny', \App\Models\Room::class) ?? false;
    $reservationsActive = request()->routeIs('reservations.index')
        || request()->routeIs('reservations.create')
        || request()->routeIs('reservations.show')
        || request()->routeIs('reservations.edit');
@endphp

<aside
    id="appSidebar"
    class="offcanvas-lg offcanvas-start app-sidebar"
    tabindex="-1"
    aria-labelledby="appSidebarLabel"
>
    <div class="offcanvas-header d-lg-none border-bottom">
        <div class="app-sidebar-brand-compact">Meet</div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar" aria-label="Fechar"></button>
    </div>

    <div class="offcanvas-body p-0 d-flex flex-column">
        <div class="app-sidebar-brand d-none d-lg-flex">
            <a href="{{ route('reservations.index') }}" class="app-sidebar-brand-link">
                <span class="app-sidebar-brand-wordmark">Meet</span>
            </a>
        </div>

        <div class="app-sidebar-section-label">Principal</div>

        <nav class="app-sidebar-nav">
            <a class="app-side-link {{ $reservationsActive ? 'is-active' : '' }}" href="{{ route('reservations.index') }}">
                <span class="app-side-link-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M7 3v3M17 3v3M4 9h16M6 6h12a2 2 0 0 1 2 2v10a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="app-side-link-copy">
                    <span class="app-side-link-label">Agendamentos</span>
                </span>
            </a>

            <a class="app-side-link {{ request()->routeIs('reservations.history') ? 'is-active' : '' }}" href="{{ route('reservations.history') }}">
                <span class="app-side-link-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M12 8v5l3 2M3 12a9 9 0 1 0 3-6.708M3 4v4h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="app-side-link-copy">
                    <span class="app-side-link-label">Historico</span>
                </span>
            </a>
            <a
                class="app-side-link {{ request()->routeIs('rooms.*') ? 'is-active' : '' }} {{ $canViewRooms ? '' : 'is-disabled' }}"
                href="{{ $canViewRooms ? route('rooms.index') : '#' }}"
                @if (! $canViewRooms) aria-disabled="true" tabindex="-1" @endif
            >
                <span class="app-side-link-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M4 20V6.5A1.5 1.5 0 0 1 5.5 5h13A1.5 1.5 0 0 1 20 6.5V20M9 20v-4h6v4M8 9h.01M16 9h.01M8 13h.01M16 13h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="app-side-link-copy">
                    <span class="app-side-link-label">Salas</span>
                </span>
            </a>

            @if ($usersRoute)
                <a
                    class="app-side-link {{ request()->routeIs('users.*') ? 'is-active' : '' }}"
                    href="{{ $usersRoute }}"
                >
                    <span class="app-side-link-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M16 19a4 4 0 0 0-8 0M12 12a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM5 19a7 7 0 0 1 14 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span class="app-side-link-copy">
                        <span class="app-side-link-label">Usuarios</span>
                    </span>
                </a>
            @endif
        </nav>

        <div class="app-sidebar-account mt-auto">
            <div class="app-sidebar-account-menu">
                <a href="{{ route('profile.edit') }}" class="app-side-link app-side-link-account">
                    <span class="app-side-link-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span class="app-side-link-copy">
                        <span class="app-side-link-label">Usuario</span>
                    </span>
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="app-side-link app-side-link-account app-side-link-danger w-100 text-start">
                        <span class="app-side-link-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M15 17l5-5-5-5M20 12H9M12 19H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span class="app-side-link-copy">
                            <span class="app-side-link-label">Sair do Sistema</span>
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>
