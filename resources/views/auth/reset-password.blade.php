<x-guest-layout>
    <div class="mb-4 app-auth-intro">
        <span class="app-page-eyebrow">Recuperacao</span>
        <h2 class="h4 mb-1">Redefinir senha</h2>
        <p class="text-body-secondary mb-0">Cadastre uma nova senha para voltar ao sistema.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <label for="email" class="form-label app-form-label">Email</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" />
            @error('email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="mt-4">
            <label for="password" class="form-label app-form-label">Senha</label>
            <input id="password" class="form-control" type="password" name="password" required autocomplete="new-password" />
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="mt-4">
            <label for="password_confirmation" class="form-label app-form-label">Confirmar senha</label>
            <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required autocomplete="new-password" />
            @error('password_confirmation')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary app-btn-primary">
                Redefinir senha
            </button>
        </div>
    </form>
</x-guest-layout>
