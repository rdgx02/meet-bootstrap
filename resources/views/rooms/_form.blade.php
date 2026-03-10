@php
    $isEdit = isset($room);
    $action = $isEdit ? route('rooms.update', $room) : route('rooms.store');
@endphp

<div class="col-12 col-lg-8 col-xl-6 mx-auto">
    <div class="app-page-header">
        <h1 class="app-section-title">{{ $isEdit ? 'Editar Sala' : 'Nova Sala' }}</h1>
        <p class="app-section-subtitle">
            {{ $isEdit ? 'Atualize os dados da sala.' : 'Cadastre uma nova sala para uso na agenda.' }}
        </p>
    </div>

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

    <form method="POST" action="{{ $action }}" class="app-card p-4 p-md-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="mb-4">
            <label for="name" class="form-label">Nome da sala</label>
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

        <div class="form-check form-switch mb-4">
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

        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 border-top pt-4">
            <p class="small text-body-secondary mb-0">Use sala inativa para impedir novos agendamentos sem apagar historico.</p>

            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('rooms.index') }}" class="btn btn-outline-secondary">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    {{ $isEdit ? 'Salvar alteracoes' : 'Criar sala' }}
                </button>
            </div>
        </div>
    </form>
</div>
