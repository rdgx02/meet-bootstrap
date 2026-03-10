@extends('layouts.app')

@section('title', 'Editar Sala')

@section('content')
    @include('rooms._form', ['room' => $room])
@endsection
