@php
    use Carbon\Carbon;
@endphp

@extends('layouts.app')

@section('title', 'Detalhe da Série')

@section('content')
    @php
        $futureOccurrences = $series->reservations->filter(function ($reservation) use ($now): bool {
            return Carbon::parse(sprintf('%s %s', $reservation->date, $reservation->start_time))->greaterThan($now);
        });
        $exceptionCount = $series->reservations->where('is_exception', true)->count();
    @endphp

    <div class="app-module-shell" x-data="{ showCancelSeriesModal: false }">
        <section class="app-module-header">
            <div>
                <div class="app-module-kicker">Recorrência</div>
                <h1 class="app-module-title">{{ $series->title }}</h1>
                <p class="app-module-note">Detalhes da série recorrente e das ocorrências geradas.</p>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('reservation-series.index') }}" class="btn btn-outline-secondary app-section-btn app-section-btn-light">
                    Voltar
                </a>

                @can('update', $series)
                    @if ($series->status === 'active')
                        <a href="{{ route('reservation-series.edit', $series) }}" class="btn btn-outline-secondary app-section-btn app-section-btn-light">
                            Editar série
                        </a>
                    @endif
                @endcan

                @can('cancel', $series)
                    @if ($series->status === 'active')
                        <button type="button" class="btn app-btn-primary app-section-btn" x-on:click="showCancelSeriesModal = true">
                            Cancelar série
                        </button>
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
                    <h2 class="app-subpanel-title">Resumo da série</h2>
                    <p class="app-subpanel-note">Informações principais e impacto atual na agenda.</p>
                </div>
            </div>

            <div class="app-series-summary-grid">
                <article class="app-series-summary-card">
                    <span class="app-series-summary-label">Sala</span>
                    <strong class="app-series-summary-value">{{ $series->room?->name ?? '-' }}</strong>
                    <p class="app-series-summary-note">{{ $series->starts_on_br }} até {{ $series->ends_on_br }}</p>
                </article>

                <article class="app-series-summary-card">
                    <span class="app-series-summary-label">Frequência</span>
                    <strong class="app-series-summary-value">{{ $series->frequency_label }}</strong>
                    <p class="app-series-summary-note">{{ $series->start_time }} - {{ $series->end_time }}</p>
                </article>

                <article class="app-series-summary-card">
                    <span class="app-series-summary-label">Status</span>
                    <div class="app-series-summary-status">
                        <span class="app-status-pill {{ $series->status === 'active' ? 'is-active' : 'is-inactive' }}">
                            {{ $series->status === 'active' ? 'Ativa' : 'Cancelada' }}
                        </span>
                    </div>
                    <p class="app-series-summary-note">{{ $futureOccurrences->count() }} ocorrências futuras</p>
                </article>

                <article class="app-series-summary-card">
                    <span class="app-series-summary-label">Exceções</span>
                    <strong class="app-series-summary-value">{{ $exceptionCount }}</strong>
                    <p class="app-series-summary-note">{{ $series->phone ?: '-' }}</p>
                </article>
            </div>
        </section>

        <section class="app-subpanel mt-4">
            <div class="app-subpanel-head">
                <div>
                    <h2 class="app-subpanel-title">Ocorrências da série</h2>
                    <p class="app-subpanel-note">Itens gerados para essa recorrência, incluindo exceções.</p>
                </div>
            </div>

            <div class="app-data-sheet">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 app-record-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Horário</th>
                                <th>Status</th>
                                <th>Observação</th>
                                <th class="text-end">Ações</th>
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
                                            {{ $hasStarted ? 'Ocorrência passada/ativa' : 'Ocorrência futura' }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $reservation->is_exception ? 'Editada como exceção da série' : 'Sem alterações manuais' }}
                                    </td>
                                    <td>
                                        <div class="app-inline-actions justify-content-end">
                                            <a href="{{ route('reservations.show', $reservation) }}?from=series&series={{ $series->id }}" class="btn btn-sm app-ghost-btn">
                                                Ver
                                            </a>

                                            @can('update', $reservation)
                                                @if (! $hasStarted)
                                                    <a href="{{ route('reservations.edit', $reservation) }}?from=series&series={{ $series->id }}" class="btn btn-sm app-ghost-btn">
                                                        Editar ocorrência
                                                    </a>
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-body-secondary py-5">
                                        Nenhuma ocorrência encontrada para esta série.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        @can('cancel', $series)
            @if ($series->status === 'active')
                <template x-if="showCancelSeriesModal">
                    <div>
                        <div class="app-modal-backdrop" x-on:click="showCancelSeriesModal = false"></div>

                        <div class="app-modal-shell" role="dialog" aria-modal="true" aria-labelledby="cancelSeriesTitle">
                            <div class="app-modal-card">
                                <div class="app-modal-header">
                                    <div>
                                        <span class="app-modal-kicker">Confirmar cancelamento</span>
                                        <h2 id="cancelSeriesTitle" class="app-modal-title">Cancelar série recorrente?</h2>
                                    </div>

                                    <button type="button" class="btn-close" aria-label="Fechar" x-on:click="showCancelSeriesModal = false"></button>
                                </div>

                                <div class="app-modal-body">
                                    <div class="app-delete-alert">
                                        Essa ação encerrará a recorrência e removerá as ocorrências futuras ainda não iniciadas.
                                    </div>

                                    <p class="app-modal-text">
                                        O cancelamento afetará {{ $futureOccurrences->count() }} ocorrência(s) futura(s) desta série.
                                    </p>

                                    <div class="app-modal-summary">
                                        <div><span>Série</span><strong>{{ $series->title }}</strong></div>
                                        <div><span>Sala</span><strong>{{ $series->room?->name ?? '-' }}</strong></div>
                                        <div><span>Período</span><strong>{{ $series->starts_on_br }} até {{ $series->ends_on_br }}</strong></div>
                                        <div><span>Ocorrências futuras</span><strong>{{ $futureOccurrences->count() }}</strong></div>
                                    </div>
                                </div>

                                <div class="app-modal-footer">
                                    <button type="button" class="btn btn-outline-secondary app-section-btn app-section-btn-light" x-on:click="showCancelSeriesModal = false">
                                        Voltar
                                    </button>

                                    <form method="POST" action="{{ route('reservation-series.cancel', $series) }}">
                                        @csrf
                                        @method('PATCH')

                                        <button type="submit" class="btn btn-danger app-delete-confirm-btn">
                                            Confirmar cancelamento
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            @endif
        @endcan
    </div>
@endsection
