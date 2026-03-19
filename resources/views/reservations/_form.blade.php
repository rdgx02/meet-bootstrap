@php
    $isEdit = isset($reservation);
    $formAction = $isEdit ? route('reservations.update', $reservation) : route('reservations.store');
    $backUrl = $returnToSeries ?? route('reservations.index');
    $dateValue = old('date', $isEdit ? $reservation->date : now()->toDateString());
    $startValue = old('start_time', $isEdit ? $reservation->start_time : '08:00');
    $endValue = old('end_time', $isEdit ? $reservation->end_time : '09:00');
    $bookingMode = old('booking_mode', 'single');
    $recurrenceStartsOn = old('recurrence_starts_on', now()->toDateString());
    $recurrenceEndsOn = old('recurrence_ends_on', now()->addMonthsNoOverflow(6)->toDateString());
    $recurrenceFrequency = old('recurrence_frequency', 'weekly');
    $recurrenceWeekdays = collect(old('recurrence_weekdays', []))->map(fn (mixed $value): string => (string) $value)->all();
    $conflictContext = session('reservation_conflict');
    $conflictDetails = is_array($conflictContext) ? $conflictContext : [];
    $conflictMessage = $errors->first('start_time');
    $showConflictAlert = $conflictDetails !== [] && $conflictMessage !== '';
    $recurringConflictItems = collect(session('recurring_conflicts', []));
    $recurringConflictMessage = $errors->first('recurrence_ends_on');
    $showRecurringConflictAlert = ! $isEdit && $recurringConflictItems->isNotEmpty() && $recurringConflictMessage !== '';
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

    if ($showRecurringConflictAlert) {
        $otherErrors = $otherErrors->reject(fn (string $error): bool => $error === $recurringConflictMessage)->values();
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

    @if ($showRecurringConflictAlert)
        <div class="alert alert-warning app-warning-alert" role="alert">
            <div class="app-alert-stack">
                <div>
                    <p class="fw-semibold mb-1">Nao foi possivel criar a serie recorrente</p>
                    <p class="small mb-0">{{ $recurringConflictMessage }}</p>
                </div>

                <div class="app-recurring-conflict-list">
                    @foreach ($recurringConflictItems->take(8) as $conflict)
                        <div class="app-card-soft p-3">
                            <span class="app-alert-label">Ocorrencia com conflito</span>
                            <strong>{{ $conflict['attempted_date'] ?? '-' }} | {{ $conflict['attempted_start_time'] ?? '--:--' }} - {{ $conflict['attempted_end_time'] ?? '--:--' }}</strong>
                            <small>
                                Sala {{ $conflict['room_name'] ?? '-' }} ocupada por {{ $conflict['existing_title'] ?? '-' }}
                                ({{ $conflict['existing_start_time'] ?? '--:--' }} - {{ $conflict['existing_end_time'] ?? '--:--' }})
                            </small>
                        </div>
                    @endforeach
                </div>

                @if ($recurringConflictItems->count() > 8)
                    <p class="small mb-0 text-body-secondary">
                        Mais {{ $recurringConflictItems->count() - 8 }} ocorrencias com conflito nao foram exibidas nesta lista.
                    </p>
                @endif
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

    <form
        method="POST"
        action="{{ $formAction }}"
        class="app-subpanel app-form-sheet"
        @if (! $isEdit)
            x-data="{
                bookingMode: '{{ $bookingMode }}',
                recurrenceFrequency: '{{ $recurrenceFrequency }}'
            }"
        @endif
    >
        @csrf
        @if ($isEdit)
            @method('PUT')
            @if (! empty($returnToSeries) && isset($reservation) && $reservation->series_id)
                <input type="hidden" name="from" value="series">
                <input type="hidden" name="series" value="{{ $reservation->series_id }}">
            @endif
        @endif

        <div class="app-subpanel-head">
            <div>
                <h2 class="app-subpanel-title">Dados do agendamento</h2>
                <p class="app-subpanel-note">Formulario operacional para controle de reservas de sala.</p>
            </div>
        </div>

        <div class="app-form-grid-compact app-form-grid-2">
            @if (! $isEdit)
                <div class="app-form-field app-form-field-full">
                    <label class="app-form-label">Tipo de agendamento</label>
                    <div class="app-choice-grid">
                        <label class="app-choice-card" :class="{ 'is-active': bookingMode === 'single' }">
                            <input
                                type="radio"
                                name="booking_mode"
                                value="single"
                                x-model="bookingMode"
                                {{ $bookingMode === 'single' ? 'checked' : '' }}
                            >
                            <span class="app-choice-card-body">
                                <strong>Agendamento unico</strong>
                                <small>Cria uma unica reserva para a data escolhida.</small>
                            </span>
                        </label>

                        <label class="app-choice-card" :class="{ 'is-active': bookingMode === 'recurring' }">
                            <input
                                type="radio"
                                name="booking_mode"
                                value="recurring"
                                x-model="bookingMode"
                                {{ $bookingMode === 'recurring' ? 'checked' : '' }}
                            >
                            <span class="app-choice-card-body">
                                <strong>Agendamento recorrente</strong>
                                <small>Gera automaticamente uma serie de reservas no periodo informado.</small>
                            </span>
                        </label>
                    </div>
                </div>
            @else
                <input type="hidden" name="booking_mode" value="single">
            @endif

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

            <div class="app-form-field" @if (! $isEdit) x-show="bookingMode === 'single'" x-cloak @endif>
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

            @if (! $isEdit)
                <div class="app-form-field" x-show="bookingMode === 'recurring'" x-cloak>
                    <label for="recurrence_starts_on" class="app-form-label">Data de inicio</label>
                    <input
                        id="recurrence_starts_on"
                        type="text"
                        name="recurrence_starts_on"
                        value="{{ $recurrenceStartsOn }}"
                        data-min-date="{{ now()->toDateString() }}"
                        class="js-date-picker form-control @error('recurrence_starts_on') is-invalid @enderror"
                        placeholder="dd/mm/aaaa"
                    >
                    @error('recurrence_starts_on')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="app-form-field" x-show="bookingMode === 'recurring'" x-cloak>
                    <label for="recurrence_ends_on" class="app-form-label">Data de termino</label>
                    <input
                        id="recurrence_ends_on"
                        type="text"
                        name="recurrence_ends_on"
                        value="{{ $recurrenceEndsOn }}"
                        data-min-date="{{ now()->toDateString() }}"
                        class="js-date-picker form-control @error('recurrence_ends_on') is-invalid @enderror"
                        placeholder="dd/mm/aaaa"
                    >
                    @error('recurrence_ends_on')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            @endif

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

            @if (! $isEdit)
                <div class="app-form-field" x-show="bookingMode === 'recurring'" x-cloak>
                    <label for="recurrence_frequency" class="app-form-label">Frequencia</label>
                    <select
                        id="recurrence_frequency"
                        name="recurrence_frequency"
                        class="form-select @error('recurrence_frequency') is-invalid @enderror"
                        x-model="recurrenceFrequency"
                    >
                        <option value="daily" @selected($recurrenceFrequency === 'daily')>Diaria</option>
                        <option value="weekly" @selected($recurrenceFrequency === 'weekly')>Semanal</option>
                        <option value="monthly" @selected($recurrenceFrequency === 'monthly')>Mensal</option>
                    </select>
                    @error('recurrence_frequency')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            @endif

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
                @if (! $isEdit)
                    <div x-show="bookingMode === 'recurring' && recurrenceFrequency === 'weekly'" x-cloak>
                        <label class="app-form-label">Dias da semana</label>
                        <div class="app-weekday-grid">
                            @foreach ([
                                1 => 'Seg',
                                2 => 'Ter',
                                3 => 'Qua',
                                4 => 'Qui',
                                5 => 'Sex',
                                6 => 'Sab',
                                7 => 'Dom',
                            ] as $weekdayValue => $weekdayLabel)
                                <label class="app-weekday-chip">
                                    <input
                                        type="checkbox"
                                        name="recurrence_weekdays[]"
                                        value="{{ $weekdayValue }}"
                                        {{ in_array((string) $weekdayValue, $recurrenceWeekdays, true) ? 'checked' : '' }}
                                    >
                                    <span>{{ $weekdayLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('recurrence_weekdays')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                @endif
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
            <p class="small text-body-secondary mb-0">
                @if (! $isEdit)
                    <span x-show="bookingMode === 'single'" x-cloak>Revise os dados antes de confirmar a operacao.</span>
                    <span x-show="bookingMode === 'recurring'" x-cloak>O sistema verificara conflito em cada ocorrencia da serie antes de salvar.</span>
                @else
                    Revise os dados antes de confirmar a operacao.
                @endif
            </p>

            <div class="d-flex align-items-center gap-2">
                <a href="{{ $backUrl }}" class="btn btn-outline-secondary app-section-btn app-section-btn-light">
                    Cancelar
                </a>
                <button type="submit" class="btn app-btn-primary app-section-btn">
                    @if ($isEdit)
                        Salvar alteracoes
                    @else
                        <span x-show="bookingMode === 'single'" x-cloak>Criar agendamento</span>
                        <span x-show="bookingMode === 'recurring'" x-cloak>Criar serie recorrente</span>
                    @endif
                </button>
            </div>
        </div>
    </form>
</div>
