@extends('layouts.app')

@section('title', $title ?? 'Agendamentos')

@section('content')
    <div class="lims-page lims-page-samples">
        <section class="lims-page-header lims-page-header-plain">
            <div class="lims-page-header-copy">
                <h1 class="lims-page-title">{{ $scope === 'history' ? 'Historico' : 'Agendamentos' }}</h1>
            </div>

            <div class="lims-dataset-switch">
                <a
                    href="{{ route('reservations.index') }}"
                    class="lims-dataset-switch-link {{ ($scope ?? 'upcoming') === 'upcoming' ? 'is-active' : '' }}"
                >
                    Agendamentos
                </a>
                <a
                    href="{{ route('reservations.history') }}"
                    class="lims-dataset-switch-link {{ ($scope ?? 'upcoming') === 'history' ? 'is-active' : '' }}"
                >
                    Historico
                </a>
            </div>
        </section>

        <section class="lims-dataset-shell lims-dataset-shell-samples">
            <div class="lims-grid-host lims-grid-host-samples">
                <livewire:reservations-table :scope="$scope" :filters="$filters ?? []" />
            </div>
        </section>
    </div>
@endsection
