@extends('layouts.app')

@section('title', 'Detalhes do Agendamento')

@section('content')
    <div class="col-12 col-xl-9 mx-auto" x-data="{ showDeleteModal: false }">
        <div class="app-page-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h1 class="app-section-title">Detalhes do Agendamento</h1>
                <p class="app-section-subtitle">Consulte os dados completos da reserva.</p>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('reservations.index') }}" class="btn btn-outline-secondary">
                    Voltar
                </a>
                @can('update', $reservation)
                    <a href="{{ route('reservations.edit', $reservation) }}" class="btn btn-primary">
                        Editar
                    </a>
                @endcan
            </div>
        </div>

        <div class="app-card p-4 p-md-5">
            <dl class="row g-4 mb-0">
                <div class="col-md-6">
                    <dt class="small text-uppercase text-body-secondary">Data</dt>
                    <dd class="fs-5 fw-semibold mb-0">{{ $reservation->date_br }}</dd>
                </div>
                <div class="col-md-6">
                    <dt class="small text-uppercase text-body-secondary">Sala</dt>
                    <dd class="fs-5 fw-semibold mb-0">{{ $reservation->room?->name }}</dd>
                </div>
                <div class="col-md-6">
                    <dt class="small text-uppercase text-body-secondary">Hora inicio</dt>
                    <dd class="mb-0">{{ $reservation->start_time_br }}</dd>
                </div>
                <div class="col-md-6">
                    <dt class="small text-uppercase text-body-secondary">Hora fim</dt>
                    <dd class="mb-0">{{ $reservation->end_time_br }}</dd>
                </div>
                <div class="col-12">
                    <dt class="small text-uppercase text-body-secondary">Titulo</dt>
                    <dd class="mb-0">{{ $reservation->title }}</dd>
                </div>
                <div class="col-md-6">
                    <dt class="small text-uppercase text-body-secondary">Solicitante</dt>
                    <dd class="mb-0">{{ $reservation->requester }}</dd>
                </div>
                <div class="col-md-6">
                    <dt class="small text-uppercase text-body-secondary">Contato</dt>
                    <dd class="mb-0">{{ $reservation->contact ?: '-' }}</dd>
                </div>
            </dl>
        </div>

        @can('delete', $reservation)
            <div class="mt-4 d-flex justify-content-end">
                <button type="button" class="btn btn-outline-danger" x-on:click="showDeleteModal = true">
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
                                <button type="button" class="btn btn-outline-secondary" x-on:click="showDeleteModal = false">
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
