@php
    use Carbon\Carbon;
@endphp

@extends('layouts.app')

@section('title', 'Detalhe da Serie')

@section('content')
    @php
        $futureOccurrences = $series->reservations->filter(function ($reservation) use ($now): bool {
            return Carbon::parse(sprintf('%s %s', $reservation->date, $reservation->start_time))->greaterThan($now);
        });
        $exceptionCount = $series->reservations->where('is_exception', true)->count();
    @endphp

    <div class="app-module-shell">
        <section class="app-module-header">
            <div>
                <div class="app-module-kicker">Recorrencia</div>
                <h1 class="app-module-title">{{ $series->title }}</h1>
                <p class="app-module-note">Detalhes da serie recorrente e das ocorrencias geradas.</p>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('reservation-series.index') }}" class="btn btn-outline-secondary app-section-btn app-section-btn-light">
                    Voltar
                </a>

                @can('update', $series)
                    @if ($series->status === 'active')
                        <a href="{{ route('reservation-series.edit', $series) }}" class="btn btn-outline-secondary app-section-btn app-section-btn-light">
                            Editar serie
                        </a>
                    @endif
                @endcan

                @can('cancel', $series)
                    @if ($series->status === 'active')
                        <form method="POST" action="{{ route('reservation-series.cancel', $series) }}">
                            @csrf
                            @method('PATCH')

                            <button type="submit" class="btn app-btn-primary app-section-btn">
                                Cancelar serie
                            </button>
                        </form>
                    @endif
                @endcan
            </div>
        </section>

        @if (session('success'))
            <div class="alert alert-success app-success-alert" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning app-warning-alert" role="alert">
                {{ session('warning') }}
            </div>
        @endif

        <section class="app-subpanel">
            <div class="app-subpanel-head">
                <div>
                    <h2 class="app-subpanel-title">Resumo da serie</h2>
                    <p class="app-subpanel-note">Informacoes principais e impacto atual na agenda.</p>
                </div>
            </div>

            <div class="app-alert-grid">
                <div class="app-card-soft p-3">
                    <span class="app-alert-label">Sala</span>
                    <strong>{{ $series->room?->name ?? '-' }}</strong>
                    <small>{{ $series->starts_on_br }} ate {{ $series->ends_on_br }}</small>
                </div>
                <div class="app-card-soft p-3">
                    <span class="app-alert-label">Frequencia</span>
                    <strong>{{ $series->frequency_label }}</strong>
                    <small>{{ $series->start_time }} - {{ $series->end_time }}</small>
                </div>
                <div class="app-card-soft p-3">
                    <span class="app-alert-label">Status</span>
                    <strong>{{ $series->status === 'active' ? 'Ativa' : 'Cancelada' }}</strong>
                    <small>{{ $futureOccurrences->count() }} ocorrencias futuras</small>
                </div>
                <div class="app-card-soft p-3">
                    <span class="app-alert-label">Excecoes</span>
                    <strong>{{ $exceptionCount }}</strong>
                    <small>{{ $series->user?->name ?? '-' }}</small>
                </div>
            </div>
        </section>

        <section class="app-subpanel mt-4">
            <div class="app-subpanel-head">
                <div>
                    <h2 class="app-subpanel-title">Ocorrencias da serie</h2>
                    <p class="app-subpanel-note">Itens gerados para essa recorrencia, incluindo excecoes.</p>
                </div>
            </div>

            <div class="app-data-sheet">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 app-record-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Horario</th>
                                <th>Status</th>
                                <th>Observacao</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($series->reservations as $reservation)
                                @php
                                    $hasStarted = Carbon::parse(sprintf('%s %s', $reservation->date, $reservation->start_time))->lessThanOrEqualTo($now);
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $reservation->date_br }}</td>
                                    <td>{{ $reservation->start_time_br }} - {{ $reservation->end_time_br }}</td>
                                    <td>
                                        <span class="app-status-pill {{ $hasStarted ? 'is-inactive' : 'is-active' }}">
                                            {{ $hasStarted ? 'Ocorrencia passada/ativa' : 'Ocorrencia futura' }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $reservation->is_exception ? 'Editada como excecao da serie' : 'Sem alteracoes manuais' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-body-secondary py-5">
                                        Nenhuma ocorrencia encontrada para esta serie.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
@endsection
