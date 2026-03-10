@extends('layouts.app')

@section('title', 'Editar Agendamento')

@section('content')
    @include('reservations._form', ['reservation' => $reservation])
@endsection
