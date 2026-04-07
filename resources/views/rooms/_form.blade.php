@php
    $isEdit = isset($room);
    $action = $isEdit ? route('rooms.update', $room) : route('rooms.store');
@endphp

<div class="app-module-shell app-module-shell-form">
    <section class="app-module-header">
        <div>
            <div class="app-module-kicker">Cadastro</div>
            <h1 class="app-module-title">{{ $isEdit ? 'Editar Sala' : 'Nova Sala' }}</h1>
            <p class="app-module-note">
                {{ $isEdit ? 'Atualize os dados estruturais da sala.' : 'Cadastre um novo ambiente para uso na agenda institucional.' }}
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
                <h2 class="app-subpanel-title">Dados da sala</h2>
                <p class="app-subpanel-note">Preencha os campos abaixo para manter o cadastro atualizado.</p>
            </div>
        </div>

        <div class="app-form-grid-compact">
            <div class="app-form-field">
                <label for="name" class="app-form-label">Nome da sala</label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name', $isEdit ? $room->name : '') }}"
                    maxlength="255"
                    required
                    class="form-control @error('name') is-invalid @enderror"
                    placeholder="Ex.: Sala 203"
                >
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="app-form-field app-form-toggle-row">
                <div>
                    <label for="is_active" class="app-form-label">Disponibilidade</label>
                    <p class="app-field-hint mb-0">Controle se a sala pode receber novos agendamentos.</p>
                </div>
                <div class="form-check form-switch app-form-switch mb-0">
                    <input type="hidden" name="is_active" value="0">
                    <input
                        id="is_active"
                        type="checkbox"
                        name="is_active"
                        value="1"
                        class="form-check-input"
                        @checked((bool) old('is_active', $isEdit ? $room->is_active : true))
                    >
                    <label for="is_active" class="form-check-label">Sala ativa</label>
                </div>
            </div>
        </div>

        <div class="app-form-actions app-form-actions-compact">
            <p class="small text-body-secondary mb-0">Use sala inativa para impedir novos agendamentos sem apagar histórico.</p>

            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('rooms.index') }}" class="btn btn-outline-secondary app-section-btn app-section-btn-light">
                    Cancelar
                </a>
                <button type="submit" class="btn app-btn-primary app-section-btn">
                    {{ $isEdit ? 'Salvar alterações' : 'Criar sala' }}
                </button>
            </div>
        </div>
    </form>
</div>
