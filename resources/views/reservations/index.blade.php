@extends('layouts.app')

@section('title', $title ?? 'Agendamentos')

@section('content')
    <div class="lims-page lims-page-samples">
        <section class="lims-page-header lims-page-header-plain">
            <h1 class="lims-page-title">{{ $scope === 'history' ? 'Histórico' : 'Agendamentos' }}</h1>
        </section>

        <section class="lims-dataset-shell lims-dataset-shell-samples">
            <div class="lims-grid-host lims-grid-host-samples">
                <livewire:reservations-table :scope="$scope" :filters="$filters ?? []" />
            </div>
        </section>
    </div>
@endsection
