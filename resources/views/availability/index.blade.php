@extends('layouts.app')

@section('title', 'Disponibilidade')

@section('content')
    <div class="lims-page">
        <section class="lims-page-header lims-page-header-plain">
            <h1 class="lims-page-title">Disponibilidade</h1>
        </section>

        <section class="app-subpanel app-availability-panel">
            <div class="app-subpanel-head app-availability-head">
                <div>
                    <h2 class="app-subpanel-title app-availability-title">Consulta por data e sala</h2>
                    <p class="app-subpanel-note">Janela consultiva padrão de {{ $openTime }} às {{ $closeTime }} para leitura operacional da ocupação.</p>
                </div>
                <div class="app-subpanel-meta">
                    <span class="lims-toolbar-stat">Data <strong>{{ $selectedDateLabel }}</strong></span>
                    <span class="lims-toolbar-stat">Salas livres <strong>{{ $freeRoomsCount }}</strong></span>
                    <span class="lims-toolbar-stat">Salas ocupadas <strong>{{ $occupiedRoomsCount }}</strong></span>
                </div>
            </div>

            <form method="GET" action="{{ route('availability.index') }}" class="app-availability-form">
                <div class="app-availability-form-fields">
                    <div class="app-availability-field">
                        <label for="availability-date" class="form-label fw-semibold mb-1">Data</label>
                        <input
                            id="availability-date"
                            name="date"
                            type="text"
                            value="{{ $selectedDate->toDateString() }}"
                            class="form-control js-date-picker app-availability-date-input"
                            data-calendar-position="below left"
                            data-calendar-append="closest"
                        >
                    </div>

                    <div class="app-availability-field">
                        <label for="availability-room" class="form-label fw-semibold mb-1">Sala</label>
                        <select id="availability-room" name="room_id" class="form-select">
                            <option value="">Todas</option>
                            @foreach ($rooms as $room)
                                <option value="{{ $room->id }}" @selected($selectedRoom?->id === $room->id)>
                                    {{ $room->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="app-availability-actions">
                    <button type="submit" class="btn btn-sm lims-toolbar-btn lims-toolbar-btn-primary app-availability-action-btn">Consultar disponibilidade</button>
                    <a href="{{ route('availability.index') }}" class="btn btn-sm lims-toolbar-btn">Hoje</a>
                </div>
            </form>
        </section>

        @if ($selectedRoom && $primaryAvailability)
            <section class="app-subpanel app-availability-primary-panel">
                <div class="app-subpanel-head">
                    <div>
                        <h2 class="app-subpanel-title app-availability-title">Sala {{ $primaryAvailability['room']->name }}</h2>
                        <p class="app-subpanel-note">Resposta principal da consulta para o dia {{ $selectedDateLabel }}.</p>
                    </div>
                    <div class="app-subpanel-meta">
                        <span class="lims-toolbar-stat">Status <strong>{{ $primaryAvailability['status_label'] }}</strong></span>
                    </div>
                </div>

                <div class="app-availability-primary-card">
                    <div class="app-availability-primary-status">
                        <span class="app-status-pill {{ $primaryAvailability['status'] === 'free' ? 'is-active' : ($primaryAvailability['status'] === 'busy' ? 'is-inactive' : 'is-neutral') }}">
                            {{ $primaryAvailability['status_label'] }}
                        </span>
                    </div>

                    <div class="app-availability-primary-sections">
                        <section class="app-availability-summary-block">
                            <h3>Horários disponíveis</h3>
                            @if ($primaryAvailability['free_ranges'] !== [])
                                <ul class="app-availability-summary-list is-free">
                                    @foreach ($primaryAvailability['free_ranges'] as $range)
                                        <li>{{ $range['label'] }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="app-availability-summary-empty">Não há faixa livre dentro da janela consultiva.</p>
                            @endif
                        </section>

                        <section class="app-availability-summary-block">
                            <h3>Horários ocupados</h3>
                            @if ($primaryAvailability['occupied_ranges'] !== [])
                                <ul class="app-availability-summary-list is-busy">
                                    @foreach ($primaryAvailability['occupied_ranges'] as $range)
                                        <li>{{ $range['label'] }} - {{ $range['title'] }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="app-availability-summary-empty">Nenhuma reserva registrada para esta sala no dia.</p>
                            @endif
                        </section>
                    </div>
                </div>
            </section>
        @else
            <section class="app-subpanel app-availability-list-panel">
                <div class="app-subpanel-head">
                    <div>
                        <h2 class="app-subpanel-title app-availability-title">Disponibilidade por sala</h2>
                        <p class="app-subpanel-note">Leitura rápida por sala, priorizando primeiro as faixas disponíveis.</p>
                    </div>
                </div>

                <div class="app-availability-room-list">
                    @foreach ($roomAvailability as $entry)
                        <article
                            class="app-availability-room-card"
                            data-availability-room="{{ $entry['room']->name }}"
                            data-availability-status="{{ $entry['status'] }}"
                        >
                            <div class="app-availability-room-card-head">
                                <div>
                                    <h3>{{ $entry['room']->name }}</h3>
                                    <p>{{ $entry['status_label'] }}</p>
                                </div>
                                <span class="app-status-pill {{ $entry['status'] === 'free' ? 'is-active' : ($entry['status'] === 'busy' ? 'is-inactive' : 'is-neutral') }}">
                                    {{ $entry['status_label'] }}
                                </span>
                            </div>

                            <section class="app-availability-summary-block">
                                <h4>Horários disponíveis</h4>
                                @if ($entry['free_ranges'] !== [])
                                    @if ($entry['is_free_all_day'])
                                        <p class="app-availability-summary-empty">Livre durante todo o período consultivo.</p>
                                    @endif
                                    <ul class="app-availability-summary-list is-free">
                                        @foreach ($entry['free_ranges'] as $range)
                                            <li>{{ $range['label'] }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="app-availability-summary-empty">Sem disponibilidade dentro da janela consultiva.</p>
                                @endif
                            </section>

                            @if ($entry['occupied_ranges'] !== [])
                                <section class="app-availability-summary-block app-availability-summary-block-secondary">
                                    <h4>Horários ocupados</h4>
                                    <ul class="app-availability-summary-list is-busy">
                                        @foreach ($entry['occupied_ranges'] as $range)
                                            <li>{{ $range['label'] }} - {{ $range['title'] }}</li>
                                        @endforeach
                                    </ul>
                                </section>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="app-availability-grid">
            <section class="app-subpanel app-availability-day-panel">
                <div class="app-subpanel-head">
                    <div>
                        <h2 class="app-subpanel-title app-availability-title">Agendamentos do dia</h2>
                        <p class="app-subpanel-note">Lista consolidada das reservas encontradas para {{ $selectedDateLabel }}.</p>
                    </div>
                    <div class="app-subpanel-meta">
                        @if ($selectedRoom)
                            <span class="lims-toolbar-stat">Sala <strong>{{ $selectedRoom->name }}</strong></span>
                        @endif
                        <span class="lims-toolbar-stat">Reservas <strong>{{ $dayReservations->count() }}</strong></span>
                    </div>
                </div>

                <div class="app-data-sheet">
                    @if ($dayReservations->isEmpty())
                        <div class="app-empty-state app-empty-state-compact">
                            Nenhum agendamento encontrado para esta data.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0 app-record-table">
                                <thead>
                                    <tr>
                                        <th>Sala</th>
                                        <th>Horário</th>
                                        <th>Título</th>
                                        <th>Solicitante</th>
                                        <th>Criado por</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dayReservations as $reservation)
                                        <tr>
                                            <td class="fw-semibold">{{ $reservation->room?->name ?? '-' }}</td>
                                            <td>{{ $reservation->start_time_br }} às {{ $reservation->end_time_br }}</td>
                                            <td>{{ $reservation->title }}</td>
                                            <td>{{ $reservation->requester }}</td>
                                            <td>{{ $reservation->user?->name ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection
