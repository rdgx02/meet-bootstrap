@extends('layouts.app')

@section('title', 'Editar Serie')

@section('content')
    @include('reservation-series._form', ['series' => $series, 'rooms' => $rooms])
@endsection
