@extends('layouts.app')

@section('title', 'Editar Série')

@section('content')
    @include('reservation-series._form', ['series' => $series, 'rooms' => $rooms])
@endsection
