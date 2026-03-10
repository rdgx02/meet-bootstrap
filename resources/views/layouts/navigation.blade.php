<nav class="navbar navbar-expand-lg sticky-top app-navbar">
    <div class="container">
        <a href="{{ route('reservations.index') }}" class="navbar-brand d-flex align-items-center gap-3 fw-semibold">
            <span class="app-brand-mark">M</span>
            <span>
                Meet
                <small class="d-block text-body-secondary fw-normal">LADETEC</small>
            </span>
        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#appNavbar"
            aria-controls="appNavbar"
            aria-expanded="false"
            aria-label="Alternar navegacao"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="appNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a
                        class="nav-link @if (request()->routeIs('reservations.index') || request()->routeIs('reservations.create') || request()->routeIs('reservations.show') || request()->routeIs('reservations.edit')) active fw-semibold @endif"
                        href="{{ route('reservations.index') }}"
                    >
                        Agendamentos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if (request()->routeIs('reservations.history')) active fw-semibold @endif" href="{{ route('reservations.history') }}">
                        Historico
                    </a>
                </li>
                @can('viewAny', \App\Models\Room::class)
                    <li class="nav-item">
                        <a class="nav-link @if (request()->routeIs('rooms.*')) active fw-semibold @endif" href="{{ route('rooms.index') }}">
                            Salas
                        </a>
                    </li>
                @endcan
            </ul>

            <div class="dropdown">
                <button
                    class="btn btn-light border dropdown-toggle d-flex align-items-center gap-2"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                >
                    <span class="app-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                    <span class="text-start">
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
        </div>
    </div>
</nav>
