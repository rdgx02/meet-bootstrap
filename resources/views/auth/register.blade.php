<x-guest-layout>
    <div class="mb-4 app-auth-intro">
        <span class="app-page-eyebrow">Cadastro</span>
        <h2 class="h4 mb-1">Criar conta</h2>
        <p class="text-body-secondary mb-0">Cadastre um usuario para acessar a agenda interna.</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div>
            <label for="name" class="form-label app-form-label">Nome</label>
            <input id="name" class="form-control" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" />
            @error('name')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <label for="email" class="form-label app-form-label">E-mail</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" />
            @error('email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password -->
        <div class="mt-4">
            <label for="password" class="form-label app-form-label">Senha</label>
            <input id="password" class="form-control" type="password" name="password" required autocomplete="new-password" />
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <label for="password_confirmation" class="form-label app-form-label">Confirmar senha</label>
            <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required autocomplete="new-password" />
            @error('password_confirmation')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mt-4">
            <a class="link-secondary text-decoration-none" href="{{ route('login') }}">
                Ja possui conta?
            </a>

            <button type="submit" class="btn btn-primary app-btn-primary">
                Cadastrar
            </button>
        </div>
    </form>
</x-guest-layout>
