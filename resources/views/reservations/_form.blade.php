@php
    $isEdit = isset($reservation);
    $formAction = $isEdit ? route('reservations.update', $reservation) : route('reservations.store');
    $dateValue = old('date', $isEdit ? $reservation->date : now()->toDateString());
    $startValue = old('start_time', $isEdit ? $reservation->start_time : '08:00');
    $endValue = old('end_time', $isEdit ? $reservation->end_time : '09:00');
    $conflictContext = session('reservation_conflict');
    $conflictDetails = is_array($conflictContext) ? $conflictContext : [];
    $conflictMessage = $errors->first('start_time');
    $showConflictAlert = $conflictDetails !== [] && $conflictMessage !== '';
    $conflictRoomName = $conflictDetails['room_name'] ?? '-';
    $conflictDate = $conflictDetails['date'] ?? '-';
    $conflictStart = $conflictDetails['start_time'] ?? '--:--';
    $conflictEnd = $conflictDetails['end_time'] ?? '--:--';
    $conflictTitle = $conflictDetails['title'] ?? '-';
    $conflictRequester = $conflictDetails['requester'] ?? '-';
    $otherErrors = collect($errors->all());

    if ($showConflictAlert) {
        $otherErrors = $otherErrors->reject(fn (string $error): bool => $error === $conflictMessage)->values();
    }
@endphp

<div class="col-12 col-xl-9 mx-auto">
    <div class="app-page-header">
        <h1 class="app-section-title">{{ $isEdit ? 'Editar Agendamento' : 'Novo Agendamento' }}</h1>
        <p class="app-section-subtitle">
            {{ $isEdit ? 'Atualize os dados e salve as alteracoes.' : 'Preencha os dados para registrar um novo horario.' }}
        </p>
    </div>

    @if ($showConflictAlert)
        <div class="alert alert-warning border-0 shadow-sm" role="alert">
            <div class="d-flex align-items-start gap-3">
                <div class="fs-4 lh-1">!</div>
                <div class="w-100">
                    <p class="fw-semibold mb-1">Horario indisponivel para essa sala</p>
                    <p class="small mb-3">{{ $conflictMessage }}</p>

                    <div class="app-card-soft p-3 mb-3">
                        <p class="text-uppercase small fw-semibold text-warning-emphasis mb-1">Horario ocupado</p>
                        <p class="h5 mb-1">{{ $conflictStart }} - {{ $conflictEnd }}</p>
                        <p class="mb-0">{{ $conflictDate }} | Sala {{ $conflictRoomName }}</p>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="app-card-soft p-3 h-100">
                                <p class="text-uppercase small fw-semibold text-warning-emphasis mb-1">Titulo da reserva existente</p>
                                <p class="mb-0 fw-medium">{{ $conflictTitle }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="app-card-soft p-3 h-100">
                                <p class="text-uppercase small fw-semibold text-warning-emphasis mb-1">Solicitante</p>
                                <p class="mb-0 fw-medium">{{ $conflictRequester }}</p>
                            </div>
                        </div>
                    </div>

                    <p class="small mb-0">Sugestao: escolha outro horario livre ou altere a sala para concluir o agendamento.</p>
                </div>
            </div>
        </div>
    @endif

    @if ($otherErrors->isNotEmpty())
        <div class="alert alert-danger" role="alert">
            <p class="fw-semibold mb-2">Nao foi possivel salvar:</p>
            <ul class="mb-0 ps-3">
                @foreach ($otherErrors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $formAction }}" class="app-card p-4 p-md-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="row g-4">
            <div class="col-12">
                <label for="room_id" class="form-label">Sala</label>
                <select
                    id="room_id"
                    name="room_id"
                    required
                    class="form-select @error('room_id') is-invalid @enderror"
                >
                    <option value="">Selecione</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->id }}" @selected((string) old('room_id', $isEdit ? $reservation->room_id : '') === (string) $room->id)>
                            {{ $room->name }}
                        </option>
                    @endforeach
                </select>
                @error('room_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="date" class="form-label">Data</label>
                <input
                    id="date"
                    type="text"
                    name="date"
                    value="{{ $dateValue }}"
                    data-min-date="{{ now()->toDateString() }}"
                    required
                    class="js-date-picker form-control @error('date') is-invalid @enderror"
                    placeholder="dd/mm/aaaa"
                >
                @error('date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="requester" class="form-label">Solicitante</label>
                <input
                    id="requester"
                    type="text"
                    name="requester"
                    value="{{ old('requester', $isEdit ? $reservation->requester : '') }}"
                    maxlength="255"
                    required
                    class="form-control @error('requester') is-invalid @enderror"
                    placeholder="Nome de quem pediu a reserva"
                >
                @error('requester')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="start_time" class="form-label">Hora inicio</label>
                <input
                    id="start_time"
                    type="time"
                    name="start_time"
                    value="{{ $startValue }}"
                    required
                    class="form-control @error('start_time') is-invalid @enderror"
                >
                @error('start_time')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="end_time" class="form-label">Hora fim</label>
                <input
                    id="end_time"
                    type="time"
                    name="end_time"
                    value="{{ $endValue }}"
                    required
                    class="form-control @error('end_time') is-invalid @enderror"
                >
                @error('end_time')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <label for="title" class="form-label">Titulo</label>
                <input
                    id="title"
                    type="text"
                    name="title"
                    value="{{ old('title', $isEdit ? $reservation->title : '') }}"
                    maxlength="255"
                    required
                    class="form-control @error('title') is-invalid @enderror"
                    placeholder="Ex.: Reuniao de equipe"
                >
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <label for="contact" class="form-label">Contato (opcional)</label>
                <input
                    id="contact"
                    type="text"
                    name="contact"
                    value="{{ old('contact', $isEdit ? $reservation->contact : '') }}"
                    maxlength="255"
                    class="form-control @error('contact') is-invalid @enderror"
                    placeholder="Telefone, ramal ou e-mail"
                >
                @error('contact')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 border-top pt-4 mt-4">
            <p class="small text-body-secondary mb-0">Revise os dados e clique em salvar para concluir.</p>

            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('reservations.index') }}" class="btn btn-outline-secondary">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    {{ $isEdit ? 'Salvar alteracoes' : 'Criar agendamento' }}
                </button>
            </div>
        </div>
    </form>
</div>
