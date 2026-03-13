<x-guest-layout>
    <div class="mb-4 app-auth-intro">
        <span class="app-page-eyebrow">Verificacao</span>
        <h2 class="h4 mb-1">Verifique seu e-mail</h2>
        <p class="text-body-secondary mb-0">Antes de continuar, confirme seu endereco pelo link enviado para sua caixa de entrada.</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success mb-4" role="alert">
            Um novo link de verificacao foi enviado para o seu e-mail.
        </div>
    @endif

    <div class="mt-4 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <button type="submit" class="btn btn-primary app-btn-primary">
                Reenviar e-mail de verificacao
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="btn btn-outline-secondary">
                Sair
            </button>
        </form>
    </div>
</x-guest-layout>
