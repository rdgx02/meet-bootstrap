@php
    use Carbon\Carbon;
@endphp

@extends('layouts.app')

@section('title', 'Series Recorrentes')

@section('content')
    <div class="app-module-shell">
        <section class="app-module-header">
            <div>
                <div class="app-module-kicker">Recorrencia</div>
                <h1 class="app-module-title">Series recorrentes</h1>
                <p class="app-module-note">Acompanhe series ativas e canceladas criadas para a agenda institucional.</p>
            </div>

            <a href="{{ route('reservations.create') }}" class="btn app-btn-primary app-section-btn">
                Nova recorrencia
            </a>
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
                    <h2 class="app-subpanel-title">Lista de series</h2>
                    <p class="app-subpanel-note">Visao operacional das recorrencias configuradas no sistema.</p>
                </div>
                <div class="app-subpanel-meta">
                    <span class="app-mini-stat">Total <strong>{{ $seriesCollection->count() }}</strong></span>
                </div>
            </div>

            <div class="app-data-sheet">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 app-record-table">
                        <thead>
                            <tr>
                                <th>Titulo</th>
                                <th>Sala</th>
                                <th>Periodo</th>
                                <th>Frequencia</th>
                                <th>Status</th>
                                <th class="text-end">Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($seriesCollection as $series)
                                @php
                                    $futureCount = $series->reservations->filter(function ($reservation) use ($now): bool {
                                        return Carbon::parse(sprintf('%s %s', $reservation->date, $reservation->start_time))->greaterThan($now);
                                    })->count();
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $series->title }}</strong>
                                        <div class="small text-body-secondary">{{ $series->requester }}</div>
                                    </td>
                                    <td>{{ $series->room?->name ?? '-' }}</td>
                                    <td>{{ $series->starts_on_br }} ate {{ $series->ends_on_br }}</td>
                                    <td>{{ $series->frequency_label }}</td>
                                    <td>
                                        <span class="app-status-pill {{ $series->status === 'active' ? 'is-active' : 'is-inactive' }}">
                                            {{ $series->status === 'active' ? 'Ativa' : 'Cancelada' }}
                                        </span>
                                        <div class="small text-body-secondary mt-1">{{ $futureCount }} futuras</div>
                                    </td>
                                    <td>
                                        <div class="app-inline-actions justify-content-end">
                                            <a href="{{ route('reservation-series.show', $series) }}" class="btn btn-sm app-ghost-btn">
                                                Detalhes
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-body-secondary py-5">
                                        Nenhuma serie recorrente cadastrada.
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
