@extends('layouts.app')

@section('title', 'Disponibilidade')

@section('content')
    <div class="app-module-shell">
        <section class="app-module-header">
            <div>
                <div class="app-module-kicker">Consulta</div>
                <h1 class="app-module-title">Disponibilidade</h1>
                <p class="app-module-note">Consulte a agenda do dia e veja quais salas estao livres por faixa de horario.</p>
            </div>
        </section>

        <section class="app-subpanel app-availability-panel">
            <div class="app-subpanel-head app-availability-head">
                <div>
                    <h2 class="app-subpanel-title">Consulta por data</h2>
                    <p class="app-subpanel-note">Janela consultiva padrao de {{ $openTime }} as {{ $closeTime }} para leitura rapida da ocupacao.</p>
                </div>
                <div class="app-subpanel-meta">
                    <span class="app-mini-stat">Data <strong>{{ $selectedDateLabel }}</strong></span>
                    <span class="app-mini-stat">Salas livres <strong>{{ $freeRoomsCount }}</strong></span>
                    <span class="app-mini-stat">Salas ocupadas <strong>{{ $occupiedRoomsCount }}</strong></span>
                </div>
            </div>

            <form method="GET" action="{{ route('availability.index') }}" class="app-availability-form">
                <div class="app-availability-form-grid">
                    <div class="app-availability-field">
                        <label for="availability-date" class="app-form-label">Data</label>
                        <input
                            id="availability-date"
                            name="date"
                            type="date"
                            value="{{ $selectedDate->toDateString() }}"
                            class="form-control"
                        >
                    </div>
                </div>

                <div class="app-availability-actions">
                    <button type="submit" class="btn app-btn-primary app-section-btn">Consultar dia</button>
                    <a href="{{ route('availability.index') }}" class="btn btn-outline-secondary app-section-btn app-section-btn-light">Hoje</a>
                </div>
            </form>
        </section>

        <div class="app-availability-grid">
            <section class="app-subpanel app-availability-day-panel">
                <div class="app-subpanel-head">
                    <div>
                        <h2 class="app-subpanel-title">Agendamentos do dia</h2>
                        <p class="app-subpanel-note">Lista consolidada das reservas encontradas para {{ $selectedDateLabel }}.</p>
                    </div>
                    <div class="app-subpanel-meta">
                        <span class="app-mini-stat">Reservas <strong>{{ $dayReservations->count() }}</strong></span>
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
                                        <th>Horario</th>
                                        <th>Titulo</th>
                                        <th>Solicitante</th>
                                        <th>Criado por</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dayReservations as $reservation)
                                        <tr>
                                            <td class="fw-semibold">{{ $reservation->room?->name ?? '-' }}</td>
                                            <td>{{ $reservation->start_time_br }} as {{ $reservation->end_time_br }}</td>
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

            <section class="app-subpanel">
                <div class="app-subpanel-head">
                    <div>
                        <h2 class="app-subpanel-title">Salas livres por horario</h2>
                        <p class="app-subpanel-note">Resumo por sala com faixas livres e, quando existir, ocupacao parcial no mesmo dia.</p>
                    </div>
                </div>

                <div class="app-availability-cards">
                    @foreach ($roomAvailability as $entry)
                        <article class="app-availability-card">
                            <div class="app-availability-card-head">
                                <div>
                                    <h3 class="app-availability-card-title">{{ $entry['room']->name }}</h3>
                                    <p class="app-availability-card-note">
                                        @if ($entry['is_free_all_day'])
                                            Livre durante todo o periodo consultivo.
                                        @else
                                            {{ $entry['reservations']->count() }} agendamento(s) neste dia.
                                        @endif
                                    </p>
                                </div>
                                <span class="app-status-pill {{ $entry['is_free_all_day'] ? 'is-active' : 'is-neutral' }}">
                                    {{ $entry['is_free_all_day'] ? 'Livre' : 'Parcial' }}
                                </span>
                            </div>

                            <div class="app-availability-ranges">
                                @forelse ($entry['free_ranges'] as $range)
                                    <span class="app-availability-range">{{ $range['label'] }}</span>
                                @empty
                                    <span class="app-availability-range is-busy">Sem faixa livre no periodo consultivo</span>
                                @endforelse
                            </div>

                            @if ($entry['reservations']->isNotEmpty())
                                <div class="app-availability-bookings">
                                    @foreach ($entry['reservations'] as $reservation)
                                        <div class="app-availability-booking">
                                            <strong>{{ $reservation->start_time_br }} as {{ $reservation->end_time_br }}</strong>
                                            <span>{{ $reservation->title }} - {{ $reservation->requester }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
@endsection
