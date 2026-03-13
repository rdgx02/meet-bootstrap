@php
    $isEdit = isset($room);
    $action = $isEdit ? route('rooms.update', $room) : route('rooms.store');
@endphp

<div class="app-form-shell app-form-shell-narrow">
    <section class="app-page-header-panel">
        <div class="app-page-header-copy">
            <div class="app-page-eyebrow">Salas</div>
            <h1 class="app-page-title">{{ $isEdit ? 'Editar Sala' : 'Nova Sala' }}</h1>
            <p class="app-page-note">
                {{ $isEdit ? 'Atualize os dados da sala.' : 'Cadastre uma nova sala para uso na agenda.' }}
            </p>
        </div>
    </section>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <p class="fw-semibold mb-2">Nao foi possivel salvar:</p>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" class="app-card app-form-panel p-4 p-md-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="mb-4">
            <label for="name" class="form-label app-form-label">Nome da sala</label>
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

        <div class="form-check form-switch mb-4 app-form-switch">
            <input type="hidden" name="is_active" value="0">
            <input
                id="is_active"
                type="checkbox"
                name="is_active"
                value="1"
                class="form-check-input"
                @checked((bool) old('is_active', $isEdit ? $room->is_active : true))
            >
            <label for="is_active" class="form-check-label">Sala ativa para novos agendamentos</label>
        </div>

        <div class="app-form-actions">
            <p class="small text-body-secondary mb-0">Use sala inativa para impedir novos agendamentos sem apagar historico.</p>

            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('rooms.index') }}" class="btn btn-outline-secondary">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary app-btn-primary">
                    {{ $isEdit ? 'Salvar alteracoes' : 'Criar sala' }}
                </button>
            </div>
        </div>
    </form>
</div>
