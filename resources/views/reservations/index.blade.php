@extends('layouts.app')

@section('title', $title ?? 'Agendamentos')

@section('content')
    <div class="app-page app-reservations-page">
        <section class="app-page-header-panel">
            <div class="app-page-header-copy">
                <div class="app-page-eyebrow">Agenda interna</div>
                <h1 class="app-page-title">{{ $title ?? 'Agendamentos' }}</h1>
                @if (filled($subtitle ?? ''))
                    <p class="app-page-note">{{ $subtitle ?? '' }}</p>
                @endif
            </div>

            <div class="app-page-header-actions">
                <div class="app-view-switch" aria-label="Escopo da agenda">
                    <a
                        href="{{ route('reservations.index') }}"
                        class="app-view-switch-link {{ ($scope ?? 'upcoming') === 'upcoming' ? 'is-active' : '' }}"
                    >
                        Agendamentos
                    </a>
                    <a
                        href="{{ route('reservations.history') }}"
                        class="app-view-switch-link {{ ($scope ?? 'upcoming') === 'history' ? 'is-active' : '' }}"
                    >
                        Historico
                    </a>
                </div>

                @if (($scope ?? 'upcoming') === 'upcoming')
                    @can('create', \App\Models\Reservation::class)
                        <a class="btn btn-primary app-btn-primary app-page-primary-action" href="{{ route('reservations.create') }}">
                            Novo agendamento
                        </a>
                    @endcan
                @endif
            </div>
        </section>

        <section class="app-table-panel">
            <livewire:reservations-table :scope="$scope" :filters="$filters ?? []" />
        </section>
    </div>
@endsection
