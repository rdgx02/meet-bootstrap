@php
    use App\Enums\UserRole;

    $isEdit = isset($user);
    $action = $isEdit ? route('users.update', $user) : route('users.store');
    $selectedRole = old('role', $isEdit ? $user->role->value : UserRole::User->value);
    $isActive = (bool) old('is_active', $isEdit ? $user->is_active : true);
@endphp

<div class="app-module-shell app-module-shell-form">
    <section class="app-module-header">
        <div>
            <div class="app-module-kicker">Administração</div>
            <h1 class="app-module-title">{{ $isEdit ? 'Editar Usuário' : 'Novo Usuário' }}</h1>
            <p class="app-module-note">
                {{ $isEdit ? 'Atualize papel, acesso e dados cadastrais do usuário.' : 'Cadastre um novo usuário com papel operacional definido.' }}
            </p>
        </div>
    </section>

    @if ($errors->any())
        <div class="alert alert-danger app-danger-alert" role="alert">
            <p class="fw-semibold mb-2">Não foi possível salvar:</p>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" class="app-subpanel app-form-sheet">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="app-subpanel-head">
            <div>
                <h2 class="app-subpanel-title">Dados do usuário</h2>
                <p class="app-subpanel-note">Defina identidade, permissão e acesso ao sistema.</p>
            </div>
        </div>

        <div class="app-form-grid-compact app-form-grid-2">
            <div class="app-form-field">
                <label for="name" class="app-form-label">Nome</label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name', $isEdit ? $user->name : '') }}"
                    maxlength="255"
                    required
                    class="form-control @error('name') is-invalid @enderror"
                >
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="app-form-field">
                <label for="email" class="app-form-label">E-mail</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email', $isEdit ? $user->email : '') }}"
                    maxlength="255"
                    required
                    class="form-control @error('email') is-invalid @enderror"
                >
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="app-form-field">
                <label for="role" class="app-form-label">Papel</label>
                <select id="role" name="role" required class="form-select @error('role') is-invalid @enderror">
                    @foreach (UserRole::cases() as $role)
                        <option value="{{ $role->value }}" @selected($selectedRole === $role->value)>
                            {{ match ($role) {
                                UserRole::Admin => 'Administrador',
                                UserRole::Secretary => 'Secretaria',
                                UserRole::User => 'Usuário',
                            } }}
                        </option>
                    @endforeach
                </select>
                @error('role')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="app-form-field app-form-toggle-row">
                <div>
                    <label for="is_active" class="app-form-label">Acesso</label>
                    <p class="app-field-hint mb-0">Usuários inativos não conseguem autenticar.</p>
                </div>
                <div class="form-check form-switch app-form-switch mb-0">
                    <input type="hidden" name="is_active" value="0">
                    <input
                        id="is_active"
                        type="checkbox"
                        name="is_active"
                        value="1"
                        class="form-check-input"
                        @checked($isActive)
                    >
                    <label for="is_active" class="form-check-label">Conta ativa</label>
                </div>
            </div>

            <div class="app-form-field">
                <label for="password" class="app-form-label">{{ $isEdit ? 'Nova senha' : 'Senha inicial' }}</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    {{ $isEdit ? '' : 'required' }}
                    class="form-control @error('password') is-invalid @enderror"
                >
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="app-form-field">
                <label for="password_confirmation" class="app-form-label">Confirmação de senha</label>
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    {{ $isEdit ? '' : 'required' }}
                    class="form-control"
                >
            </div>
        </div>

        <div class="app-form-actions app-form-actions-compact">
            <p class="small text-body-secondary mb-0">
                {{ $isEdit ? 'Deixe a senha em branco para manter a atual.' : 'A senha inicial poderá ser alterada pelo próprio usuário depois.' }}
            </p>

            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary app-section-btn app-section-btn-light">
                    Cancelar
                </a>
                <button type="submit" class="btn app-btn-primary app-section-btn">
                    {{ $isEdit ? 'Salvar alterações' : 'Criar usuário' }}
                </button>
            </div>
        </div>
    </form>
</div>
