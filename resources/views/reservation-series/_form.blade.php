@php
    $action = route('reservation-series.update', $series);
    $backUrl = $returnTo ?? route('reservation-series.show', $series);
    $recurrenceFrequency = old('recurrence_frequency', $series->frequency);
    $recurrenceWeekdays = collect(old('recurrence_weekdays', $series->weekdays ?? []))
        ->map(fn (mixed $value): string => (string) $value)
        ->all();
    $recurringConflictItems = collect(session('recurring_conflicts', []));
    $recurringConflictMessage = $errors->first('recurrence_ends_on');
    $showRecurringConflictAlert = $recurringConflictItems->isNotEmpty() && $recurringConflictMessage !== '';
    $otherErrors = collect($errors->all());

    if ($showRecurringConflictAlert) {
        $otherErrors = $otherErrors->reject(fn (string $error): bool => $error === $recurringConflictMessage)->values();
    }
@endphp

<div class="app-module-shell app-module-shell-form">
    <section class="app-module-header">
        <div>
            <div class="app-module-kicker">Recorrencia</div>
            <h1 class="app-module-title">Editar serie</h1>
            <p class="app-module-note">
                Atualize a regra recorrente. O sistema recriara apenas as ocorrencias futuras que ainda nao iniciaram.
            </p>
        </div>
    </section>

    @if ($showRecurringConflictAlert)
        <div class="alert alert-warning app-warning-alert" role="alert">
            <div class="app-alert-stack">
                <div>
                    <p class="fw-semibold mb-1">Nao foi possivel atualizar a serie</p>
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
        action="{{ $action }}"
        class="app-subpanel app-form-sheet"
        x-data="{ recurrenceFrequency: '{{ $recurrenceFrequency }}' }"
    >
        @csrf
        @method('PUT')
        @if (($returnTo ?? null) === route('reservation-series.index'))
            <input type="hidden" name="from" value="index">
        @endif

        <div class="app-subpanel-head">
            <div>
                <h2 class="app-subpanel-title">Regra da serie</h2>
                <p class="app-subpanel-note">As alteracoes valem para a serie inteira, preservando historico ja iniciado.</p>
            </div>
        </div>

        <div class="app-form-grid-compact app-form-grid-2">
            <div class="app-form-field">
                <label for="room_id" class="app-form-label">Sala</label>
                <select id="room_id" name="room_id" required class="form-select @error('room_id') is-invalid @enderror">
                    <option value="">Selecione</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->id }}" @selected((string) old('room_id', $series->room_id) === (string) $room->id)>
                            {{ $room->name }}
                        </option>
                    @endforeach
                </select>
                @error('room_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="app-form-field">
                <label for="requester" class="app-form-label">Solicitante</label>
                <input
                    id="requester"
                    type="text"
                    name="requester"
                    value="{{ old('requester', $series->requester) }}"
                    maxlength="255"
                    required
                    class="form-control @error('requester') is-invalid @enderror"
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
                    value="{{ old('title', $series->title) }}"
                    maxlength="255"
                    required
                    class="form-control @error('title') is-invalid @enderror"
                >
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="app-form-field">
                <label for="contact" class="app-form-label">Contato</label>
                <input
                    id="contact"
                    type="text"
                    name="contact"
                    value="{{ old('contact', $series->contact) }}"
                    maxlength="255"
                    class="form-control @error('contact') is-invalid @enderror"
                >
                @error('contact')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="app-form-field">
                <label for="recurrence_starts_on" class="app-form-label">Data de inicio</label>
                <input
                    id="recurrence_starts_on"
                    type="text"
                    name="recurrence_starts_on"
                    value="{{ old('recurrence_starts_on', $series->starts_on) }}"
                    class="js-date-picker form-control @error('recurrence_starts_on') is-invalid @enderror"
                    placeholder="dd/mm/aaaa"
                >
                @error('recurrence_starts_on')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="app-form-field">
                <label for="recurrence_ends_on" class="app-form-label">Data de termino</label>
                <input
                    id="recurrence_ends_on"
                    type="text"
                    name="recurrence_ends_on"
                    value="{{ old('recurrence_ends_on', $series->ends_on) }}"
                    data-min-date="{{ now()->toDateString() }}"
                    class="js-date-picker form-control @error('recurrence_ends_on') is-invalid @enderror"
                    placeholder="dd/mm/aaaa"
                >
                @error('recurrence_ends_on')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="app-form-field">
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

            <div class="app-form-field">
                <label for="start_time" class="app-form-label">Hora inicio</label>
                <input
                    id="start_time"
                    type="text"
                    name="start_time"
                    value="{{ old('start_time', \Carbon\Carbon::parse($series->start_time)->format('H:i')) }}"
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
                    value="{{ old('end_time', \Carbon\Carbon::parse($series->end_time)->format('H:i')) }}"
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
                <div x-show="recurrenceFrequency === 'weekly'" x-cloak>
                    <label class="app-form-label">Dias da semana</label>
                    <div class="app-weekday-grid">
                        @foreach ([1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sab', 7 => 'Dom'] as $weekdayValue => $weekdayLabel)
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
            </div>
        </div>

        <div class="app-form-actions app-form-actions-compact">
            <p class="small text-body-secondary mb-0">
                Ocorrencias futuras existentes serao substituidas pela nova configuracao da serie.
            </p>

            <div class="d-flex align-items-center gap-2">
                <a href="{{ $backUrl }}" class="btn btn-outline-secondary app-section-btn app-section-btn-light">
                    Cancelar
                </a>
                <button type="submit" class="btn app-btn-primary app-section-btn">
                    Salvar serie
                </button>
            </div>
        </div>
    </form>
</div>
