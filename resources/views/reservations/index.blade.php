@extends('layouts.app')

@section('title', $title ?? 'Agendamentos')

@section('content')
    <div class="col-12">
        <div class="app-hero mb-4">
            <div class="app-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-0">
                <div>
                    <span class="app-kicker">Painel</span>
                    <h1 class="app-section-title">{{ $title ?? 'Agendamentos' }}</h1>
                    <p class="app-section-subtitle">{{ $subtitle ?? '' }}</p>

                    <div class="app-view-switch mt-3">
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
                </div>

                @if (($scope ?? 'upcoming') === 'upcoming')
                    @can('create', \App\Models\Reservation::class)
                        <a class="btn btn-primary app-btn-primary app-hero-action" href="{{ route('reservations.create') }}">
                            Novo agendamento
                        </a>
                    @endcan
                @endif
            </div>
        </div>
        <livewire:reservations-table :scope="$scope" :filters="$filters ?? []" />
    </div>
@endsection
