@extends('layouts.app')

@section('title', 'Detalhes do Agendamento')

@section('content')
    <div class="app-module-shell" x-data="{ showDeleteModal: false }">
        <section class="app-module-header">
            <div>
                <div class="app-module-kicker">Consulta</div>
                <h1 class="app-module-title">Detalhes do Agendamento</h1>
                <p class="app-module-note">Visualizacao consolidada do registro selecionado na agenda.</p>
            </div>

            <div class="app-inline-actions">
                <a href="{{ route('reservations.index') }}" class="btn app-section-btn app-section-btn-light">
                    Voltar
                </a>
                @can('update', $reservation)
                    <a href="{{ route('reservations.edit', $reservation) }}" class="btn app-btn-primary app-section-btn">
                        Editar
                    </a>
                @endcan
            </div>
        </section>

        <section class="app-subpanel">
            <div class="app-subpanel-head">
                <div>
                    <h2 class="app-subpanel-title">Ficha do agendamento</h2>
                    <p class="app-subpanel-note">Dados principais da reserva em formato de consulta administrativa.</p>
                </div>
            </div>

            <div class="app-detail-grid">
                <div class="app-detail-card">
                    <span class="app-detail-label">Data</span>
                    <strong>{{ $reservation->date_br }}</strong>
                </div>
                <div class="app-detail-card">
                    <span class="app-detail-label">Sala</span>
                    <strong>{{ $reservation->room?->name }}</strong>
                </div>
                <div class="app-detail-card">
                    <span class="app-detail-label">Hora inicio</span>
                    <strong>{{ $reservation->start_time_br }}</strong>
                </div>
                <div class="app-detail-card">
                    <span class="app-detail-label">Hora fim</span>
                    <strong>{{ $reservation->end_time_br }}</strong>
                </div>
                <div class="app-detail-card app-detail-card-wide">
                    <span class="app-detail-label">Titulo</span>
                    <strong>{{ $reservation->title }}</strong>
                </div>
                <div class="app-detail-card">
                    <span class="app-detail-label">Solicitante</span>
                    <strong>{{ $reservation->requester }}</strong>
                </div>
                <div class="app-detail-card">
                    <span class="app-detail-label">Contato</span>
                    <strong>{{ $reservation->contact ?: '-' }}</strong>
                </div>
            </div>
        </section>

        @can('delete', $reservation)
            <div class="d-flex justify-content-end">
                <button type="button" class="btn app-ghost-btn app-ghost-btn-danger" x-on:click="showDeleteModal = true">
                    Excluir agendamento
                </button>
            </div>

            <template x-if="showDeleteModal">
                <div>
                    <div class="app-modal-backdrop" x-on:click="showDeleteModal = false"></div>

                    <div class="app-modal-shell" role="dialog" aria-modal="true" aria-labelledby="deleteReservationDetailTitle">
                        <div class="app-modal-card">
                            <div class="app-modal-header">
                                <div>
                                    <span class="app-modal-kicker">Confirmar exclusao</span>
                                    <h2 id="deleteReservationDetailTitle" class="app-modal-title">Excluir agendamento?</h2>
                                </div>

                                <button type="button" class="btn-close" aria-label="Fechar" x-on:click="showDeleteModal = false"></button>
                            </div>

                            <div class="app-modal-body">
                                <div class="app-delete-alert">
                                    Essa exclusao remove o agendamento da agenda e do historico administrativo.
                                </div>

                                <p class="app-modal-text">
                                    Essa acao remove o agendamento da agenda e nao pode ser desfeita.
                                </p>

                                <div class="app-modal-summary">
                                    <div><span>Titulo</span><strong>{{ $reservation->title }}</strong></div>
                                    <div><span>Data</span><strong>{{ $reservation->date_br }}</strong></div>
                                    <div><span>Horario</span><strong>{{ $reservation->start_time_br }} - {{ $reservation->end_time_br }}</strong></div>
                                    <div><span>Sala</span><strong>{{ $reservation->room?->name ?? '-' }}</strong></div>
                                </div>
                            </div>

                            <div class="app-modal-footer">
                                <button type="button" class="btn btn-outline-secondary app-section-btn app-section-btn-light" x-on:click="showDeleteModal = false">
                                    Cancelar
                                </button>

                                <form method="POST" action="{{ route('reservations.destroy', $reservation) }}">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-danger app-delete-confirm-btn">
                                        Excluir agendamento
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        @endcan
    </div>
@endsection
