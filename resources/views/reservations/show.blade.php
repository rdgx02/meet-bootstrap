@extends('layouts.app')

@section('title', 'Detalhes do Agendamento')

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-2xl font-bold text-gray-900">Detalhes do Agendamento</h2>

            <div class="flex items-center gap-2">
                <a href="{{ route('reservations.index') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Voltar
                </a>
                @can('update', $reservation)
                    <a
                        href="{{ route('reservations.edit', $reservation) }}"
                        class="inline-flex items-center rounded-md border border-transparent px-3 py-2 text-sm font-semibold shadow-sm"
                        style="background-color:#2563eb;color:#ffffff;"
                    >
                        Editar
                    </a>
                @endcan
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Data</dt>
                    <dd class="mt-1 text-base font-semibold text-gray-900">{{ $reservation->date_br }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Sala</dt>
                    <dd class="mt-1 text-base font-semibold text-gray-900">{{ $reservation->room?->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Hora inicio</dt>
                    <dd class="mt-1 text-base text-gray-900">{{ $reservation->start_time_br }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Hora fim</dt>
                    <dd class="mt-1 text-base text-gray-900">{{ $reservation->end_time_br }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Titulo</dt>
                    <dd class="mt-1 text-base text-gray-900">{{ $reservation->title }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Solicitante</dt>
                    <dd class="mt-1 text-base text-gray-900">{{ $reservation->requester }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Contato</dt>
                    <dd class="mt-1 text-base text-gray-900">{{ $reservation->contact ?: '-' }}</dd>
                </div>
            </dl>
        </div>

        @can('delete', $reservation)
            <form
                method="POST"
                action="{{ route('reservations.destroy', $reservation) }}"
                class="mt-5 flex justify-end"
                onsubmit="return confirm('Tem certeza que deseja excluir este agendamento?');"
            >
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700">
                    Excluir agendamento
                </button>
            </form>
        @endcan
    </div>
@endsection
