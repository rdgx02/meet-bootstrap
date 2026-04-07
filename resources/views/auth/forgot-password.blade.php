<x-guest-layout>
    <div class="mb-4 app-auth-intro">
        <span class="app-page-eyebrow">Recuperação</span>
        <h2 class="h4 mb-1">Esqueceu a senha?</h2>
        <p class="text-body-secondary mb-0">Informe seu e-mail para receber um link de redefinicao.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="form-label app-form-label">Email</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus />
            @error('email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary app-btn-primary">
                Enviar link de redefinicao
            </button>
        </div>
    </form>
</x-guest-layout>
