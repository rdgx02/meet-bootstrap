<x-guest-layout>
    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-4 app-auth-intro">
        <span class="app-page-eyebrow">Acesso</span>
        <h2 class="h4 mb-1">Entrar</h2>
        <p class="text-body-secondary mb-0">Use sua conta para acessar agendamentos e salas.</p>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label app-form-label">E-mail</label>
            <input
                id="email"
                class="form-control @error('email') is-invalid @enderror"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
            />
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label app-form-label">Senha</label>
            <input
                id="password"
                class="form-control @error('password') is-invalid @enderror"
                type="password"
                name="password"
                required
                autocomplete="current-password"
            />
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-check mb-4">
            <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
            <label for="remember_me" class="form-check-label">Lembrar de mim</label>
        </div>

        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            @if (Route::has('password.request'))
                <a class="link-secondary" href="{{ route('password.request') }}">
                    Esqueceu sua senha?
                </a>
            @endif

            <button type="submit" class="btn btn-primary app-btn-primary px-4">
                Entrar
            </button>
        </div>
    </form>
</x-guest-layout>
