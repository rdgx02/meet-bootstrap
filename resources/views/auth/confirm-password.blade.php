<x-guest-layout>
    <div class="mb-4 app-auth-intro">
        <span class="app-page-eyebrow">Seguranca</span>
        <h2 class="h4 mb-1">Confirme sua senha</h2>
        <p class="text-body-secondary mb-0">Esta e uma area protegida. Informe sua senha para continuar.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <label for="password" class="form-label app-form-label">Senha</label>
            <input id="password" class="form-control" type="password" name="password" required autocomplete="current-password" />
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary app-btn-primary">
                Confirmar
            </button>
        </div>
    </form>
</x-guest-layout>
