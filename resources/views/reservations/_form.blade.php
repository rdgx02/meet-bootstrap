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

<div class="app-module-shell app-module-shell-form">
    <section class="app-module-header">
        <div>
            <div class="app-module-kicker">Agenda</div>
            <h1 class="app-module-title">{{ $isEdit ? 'Editar Agendamento' : 'Novo Agendamento' }}</h1>
            <p class="app-module-note">
                {{ $isEdit ? 'Atualize os dados do registro selecionado.' : 'Preencha os campos para registrar um novo agendamento.' }}
            </p>
        </div>
    </section>

    @if ($showConflictAlert)
        <div class="alert alert-warning app-warning-alert" role="alert">
            <div class="app-alert-stack">
                <div>
                    <p class="fw-semibold mb-1">Horario indisponivel para essa sala</p>
                    <p class="small mb-0">{{ $conflictMessage }}</p>
                </div>

                <div class="app-alert-grid">
                    <div class="app-card-soft p-3">
                        <span class="app-alert-label">Horario ocupado</span>
                        <strong>{{ $conflictStart }} - {{ $conflictEnd }}</strong>
                        <small>{{ $conflictDate }} | Sala {{ $conflictRoomName }}</small>
                    </div>
                    <div class="app-card-soft p-3">
                        <span class="app-alert-label">Reserva existente</span>
                        <strong>{{ $conflictTitle }}</strong>
                        <small>{{ $conflictRequester }}</small>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($otherErrors->isNotEmpty())
        <div class="alert alert-danger app-danger-alert" role="alert">
            <p class="fw-semibold mb-2">Nao foi possivel salvar:</p>
            <ul class="mb-0 ps-3">
                @foreach ($otherErrors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $formAction }}" class="app-subpanel app-form-sheet">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="app-subpanel-head">
            <div>
                <h2 class="app-subpanel-title">Dados do agendamento</h2>
                <p class="app-subpanel-note">Formulario operacional para controle de reservas de sala.</p>
            </div>
        </div>

        <div class="app-form-grid-compact app-form-grid-2">
            <div class="app-form-field">
                <label for="room_id" class="app-form-label">Sala</label>
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

            <div class="app-form-field">
                <label for="date" class="app-form-label">Data</label>
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

            <div class="app-form-field">
                <label for="requester" class="app-form-label">Solicitante</label>
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

            <div class="app-form-field">
                <label for="title" class="app-form-label">Titulo</label>
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

            <div class="app-form-field">
                <label for="start_time" class="app-form-label">Hora inicio</label>
                <input
                    id="start_time"
                    type="text"
                    name="start_time"
                    value="{{ $startValue }}"
                    required
                    class="js-time-picker form-control @error('start_time') is-invalid @enderror"
                    inputmode="numeric"
                    placeholder="HH:MM"
                >
                @error('start_time')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="app-form-field">
                <label for="end_time" class="app-form-label">Hora fim</label>
                <input
                    id="end_time"
                    type="text"
                    name="end_time"
                    value="{{ $endValue }}"
                    required
                    class="js-time-picker form-control @error('end_time') is-invalid @enderror"
                    inputmode="numeric"
                    placeholder="HH:MM"
                >
                @error('end_time')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="app-form-field app-form-field-full">
                <label for="contact" class="app-form-label">Contato</label>
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

        <div class="app-form-actions app-form-actions-compact">
            <p class="small text-body-secondary mb-0">Revise os dados antes de confirmar a operacao.</p>

            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('reservations.index') }}" class="btn btn-outline-secondary app-section-btn app-section-btn-light">
                    Cancelar
                </a>
                <button type="submit" class="btn app-btn-primary app-section-btn">
                    {{ $isEdit ? 'Salvar alteracoes' : 'Criar agendamento' }}
                </button>
            </div>
        </div>
    </form>
</div>
