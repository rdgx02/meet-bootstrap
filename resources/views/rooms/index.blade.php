@extends('layouts.app')

@section('title', 'Salas')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Salas</h2>
                <p class="mt-1 text-sm text-gray-600">Gerencie as salas disponiveis para agendamento.</p>
            </div>

            @can('create', \App\Models\Room::class)
                <a
                    href="{{ route('rooms.create') }}"
                    class="inline-flex items-center rounded-md border border-transparent px-3 py-2 text-sm font-semibold shadow-sm"
                    style="background-color:#2563eb;color:#ffffff;"
                >
                    Nova sala
                </a>
            @endcan
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Nome</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">Acoes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($rooms as $room)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $room->name }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if ($room->is_active)
                                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">Ativa</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">Inativa</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex justify-end gap-2">
                                    @can('update', $room)
                                        <a
                                            href="{{ route('rooms.edit', $room) }}"
                                            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                        >
                                            Editar
                                        </a>
                                    @endcan

                                    @can('delete', $room)
                                        <form
                                            method="POST"
                                            action="{{ route('rooms.destroy', $room) }}"
                                            onsubmit="return confirm('Excluir esta sala? Essa acao e irreversivel.');"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700"
                                            >
                                                Excluir
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-5 text-sm text-gray-600">Nenhuma sala cadastrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
