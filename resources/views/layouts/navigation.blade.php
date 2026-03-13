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
        <div class="d-flex align-items-center gap-3">
            <img src="{{ asset('images/ladetec-logo.svg') }}" alt="LADETEC" class="app-brand-logo">
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar" aria-label="Fechar"></button>
    </div>

    <div class="offcanvas-body p-0 d-flex flex-column">
        <div class="app-sidebar-brand d-none d-lg-flex">
            <a href="{{ route('reservations.index') }}" class="app-sidebar-brand-link">
                <img src="{{ asset('images/ladetec-logo.svg') }}" alt="LADETEC" class="app-brand-logo">
            </a>
        </div>

        <div class="app-sidebar-section-label">Menu principal</div>

        <nav class="app-sidebar-nav">
            <a class="app-side-link {{ $reservationsActive ? 'is-active' : '' }}" href="{{ route('reservations.index') }}">
                <span class="app-side-link-icon">AG</span>
                <span class="app-side-link-label">Agendamentos</span>
            </a>

            <a class="app-side-link {{ request()->routeIs('reservations.history') ? 'is-active' : '' }}" href="{{ route('reservations.history') }}">
                <span class="app-side-link-icon">HI</span>
                <span class="app-side-link-label">Historico</span>
            </a>

            <a
                class="app-side-link {{ request()->routeIs('rooms.*') ? 'is-active' : '' }} {{ $canViewRooms ? '' : 'is-disabled' }}"
                href="{{ $canViewRooms ? route('rooms.index') : '#' }}"
                @if (! $canViewRooms) aria-disabled="true" tabindex="-1" @endif
            >
                <span class="app-side-link-icon">SA</span>
                <span class="app-side-link-label">Salas</span>
            </a>

            <a
                class="app-side-link {{ request()->routeIs('users.*') ? 'is-active' : '' }} {{ $usersRoute ? '' : 'is-disabled' }}"
                href="{{ $usersRoute ?? '#' }}"
                @if (! $usersRoute) aria-disabled="true" tabindex="-1" @endif
            >
                <span class="app-side-link-icon">US</span>
                <span class="app-side-link-label">Usuarios</span>
            </a>
        </nav>

        <div class="app-sidebar-footer mt-auto">
            <div class="app-sidebar-user">
                <span class="app-avatar app-avatar-soft">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                <div>
                    <strong>{{ Auth::user()->name }}</strong>
                    <small>{{ Auth::user()->email }}</small>
                </div>
            </div>
        </div>
    </div>
</aside>
