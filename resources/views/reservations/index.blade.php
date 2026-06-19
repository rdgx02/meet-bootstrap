@extends('layouts.app')

@section('title', $title ?? 'Agendamentos')

@section('content')
    <div class="lims-page lims-page-samples">
        <section class="lims-page-header lims-page-header-plain">
            <div class="lims-page-heading">
                <span class="lims-page-heading-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M7 3v3M17 3v3M4 9h16M6 6h12a2 2 0 0 1 2 2v10a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <div class="lims-page-heading-copy">
                    <h1 class="lims-page-title">{{ $scope === 'history' ? 'Histórico' : 'Agendamentos' }}</h1>
                    <p class="lims-page-count">{{ $total }} {{ $total === 1 ? 'registro' : 'registros' }}</p>
                </div>
            </div>
        </section>

        <section class="lims-dataset-shell lims-dataset-shell-samples">
            <div class="lims-grid-host lims-grid-host-samples">
                <livewire:reservations-table :scope="$scope" :filters="$filters ?? []" />
            </div>
        </section>
    </div>
@endsection
