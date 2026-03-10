@extends('layouts.app')

@section('title', 'Detalhes do Agendamento')

@section('content')
    <div class="col-12 col-xl-9 mx-auto">
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
            <form
                method="POST"
                action="{{ route('reservations.destroy', $reservation) }}"
                class="mt-4 d-flex justify-content-end"
                onsubmit="return confirm('Tem certeza que deseja excluir este agendamento?');"
            >
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">
                    Excluir agendamento
                </button>
            </form>
        @endcan
    </div>
@endsection
