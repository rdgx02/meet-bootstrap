@extends('layouts.app')

@section('title', 'Salas')

@section('content')
    <div class="col-12 col-xl-10 mx-auto">
        <div class="app-page-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <span class="app-kicker">Cadastro</span>
                <h1 class="app-section-title">Salas</h1>
                <p class="app-section-subtitle">Gerencie as salas disponiveis para agendamento.</p>
            </div>

            @can('create', \App\Models\Room::class)
                <a href="{{ route('rooms.create') }}" class="btn btn-primary app-btn-primary">
                    Nova sala
                </a>
            @endcan
        </div>

        @if (session('success'))
            <div class="alert alert-success shadow-sm" role="alert">
                {{ session('success') }}
            </div>
        @endif

        <div class="app-card overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 app-table">
                    <thead>
                        <tr>
                            <th class="px-4 py-3">Nome</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-end">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rooms as $room)
                            <tr>
                                <td class="px-4 py-3 fw-semibold">{{ $room->name }}</td>
                                <td class="px-4 py-3">
                                    @if ($room->is_active)
                                        <span class="badge rounded-pill text-bg-success">Ativa</span>
                                    @else
                                        <span class="badge rounded-pill text-bg-secondary">Inativa</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="d-flex justify-content-end gap-2 flex-wrap">
                                        @can('update', $room)
                                            <a href="{{ route('rooms.edit', $room) }}" class="btn btn-outline-secondary btn-sm">
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
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    Excluir
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-5 text-body-secondary">Nenhuma sala cadastrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
