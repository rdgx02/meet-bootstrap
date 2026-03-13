<section>
    <header>
        <h2 class="h4 mb-1">Atualizar senha</h2>
        <p class="text-body-secondary mb-0">Use uma senha forte para manter sua conta protegida.</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-4">
        @csrf
        @method('put')

        <div class="mb-4">
            <label for="update_password_current_password" class="form-label app-form-label">Senha atual</label>
            <input id="update_password_current_password" name="current_password" type="password" class="form-control" autocomplete="current-password" />
            @if ($errors->updatePassword->has('current_password'))
                <div class="invalid-feedback d-block">{{ $errors->updatePassword->first('current_password') }}</div>
            @endif
        </div>

        <div class="mb-4">
            <label for="update_password_password" class="form-label app-form-label">Nova senha</label>
            <input id="update_password_password" name="password" type="password" class="form-control" autocomplete="new-password" />
            @if ($errors->updatePassword->has('password'))
                <div class="invalid-feedback d-block">{{ $errors->updatePassword->first('password') }}</div>
            @endif
        </div>

        <div class="mb-4">
            <label for="update_password_password_confirmation" class="form-label app-form-label">Confirmar senha</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" />
            @if ($errors->updatePassword->has('password_confirmation'))
                <div class="invalid-feedback d-block">{{ $errors->updatePassword->first('password_confirmation') }}</div>
            @endif
        </div>

        <div class="d-flex align-items-center gap-3">
            <button type="submit" class="btn btn-primary app-btn-primary">Salvar</button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="small text-body-secondary mb-0"
                >Salvo.</p>
            @endif
        </div>
    </form>
</section>
