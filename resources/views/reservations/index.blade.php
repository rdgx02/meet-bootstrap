@extends('layouts.app')

@section('title', $title ?? 'Agendamentos')

@section('content')
    <div class="col-12">
        <div class="app-page-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h1 class="app-section-title">{{ $title ?? 'Agendamentos' }}</h1>
                <p class="app-section-subtitle">{{ $subtitle ?? '' }}</p>
            </div>

            @if (($scope ?? 'upcoming') === 'upcoming')
                @can('create', \App\Models\Reservation::class)
                    <a class="btn btn-primary" href="{{ route('reservations.create') }}">Novo agendamento</a>
                @endcan
            @endif
        </div>

        @if (session('success'))
            <div class="alert alert-success shadow-sm" role="alert">
                {{ session('success') }}
            </div>
        @endif

        <div class="app-card p-4 mb-4">
            <form method="GET" action="{{ route($filterRoute ?? 'reservations.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                    <label for="q" class="form-label">Buscar</label>
                    <input
                        type="text"
                        id="q"
                        name="q"
                        class="form-control"
                        value="{{ request('q') }}"
                        placeholder="Titulo, solicitante ou sala..."
                    >
                </div>

                <div class="col-12 col-md-6 col-lg-2">
                    <label for="room_id" class="form-label">Sala</label>
                    <select name="room_id" id="room_id" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->id }}" {{ request('room_id') == $room->id ? 'selected' : '' }}>
                                {{ $room->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-6 col-lg-2">
                    <label for="date_from" class="form-label">Data inicial</label>
                    <input
                        type="text"
                        id="date_from"
                        name="date_from"
                        class="form-control js-date-picker"
                        value="{{ request('date_from') }}"
                        placeholder="dd/mm/aaaa"
                    >
                </div>

                <div class="col-12 col-md-6 col-lg-2">
                    <label for="date_to" class="form-label">Data final</label>
                    <input
                        type="text"
                        id="date_to"
                        name="date_to"
                        class="form-control js-date-picker"
                        value="{{ request('date_to') }}"
                        placeholder="dd/mm/aaaa"
                    >
                </div>

                <div class="col-12 col-md-6 col-lg-2">
                    <label for="per_page" class="form-label">Por pagina</label>
                    <select id="per_page" name="per_page" class="form-select">
                        @foreach ([10, 20, 50, 100] as $n)
                            <option value="{{ $n }}" {{ (int) request('per_page', 10) === $n ? 'selected' : '' }}>
                                {{ $n }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-lg-auto">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">Aplicar</button>
                        <a class="btn btn-outline-secondary" href="{{ route($filterRoute ?? 'reservations.index', ['per_page' => request('per_page', 10)]) }}">
                            Limpar filtros
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="badge rounded-pill text-bg-light border text-dark px-3 py-2">Total: {{ $reservations->total() }}</span>
            <span class="badge rounded-pill text-bg-light border text-dark px-3 py-2">Nesta pagina: {{ $reservations->count() }}</span>
        </div>

        @if ($reservations->count() === 0)
            <div class="app-card p-4">
                <p class="mb-0 text-body-secondary">Nenhum agendamento encontrado.</p>
            </div>
        @else
            <div class="app-card overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 app-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Inicio</th>
                                <th>Fim</th>
                                <th>Sala</th>
                                <th>Titulo</th>
                                <th>Solicitante</th>
                                <th>Criado por</th>
                                <th>Editado por</th>
                                <th style="width: 280px;">Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reservations as $r)
                                <tr class="{{ auth()->id() === $r->user_id ? 'row-mine' : '' }}">
                                    <td class="fw-semibold">{{ $r->date_br }}</td>
                                    <td>{{ $r->start_time_br }}</td>
                                    <td>{{ $r->end_time_br }}</td>
                                    <td><span class="badge rounded-pill text-bg-light border text-dark">{{ $r->room?->name }}</span></td>
                                    <td><div class="app-truncate" title="{{ $r->title }}">{{ $r->title }}</div></td>
                                    <td><div class="app-truncate" title="{{ $r->requester }}">{{ $r->requester }}</div></td>
                                    <td>
                                        @if ($r->user)
                                            <div class="app-user-chip">
                                                <div class="app-avatar">{{ strtoupper(substr($r->user->name, 0, 1)) }}</div>
                                                <div class="app-truncate" title="{{ $r->user->name }}">{{ $r->user->name }}</div>
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($r->editor)
                                            <div class="app-user-chip">
                                                <div class="app-avatar">{{ strtoupper(substr($r->editor->name, 0, 1)) }}</div>
                                                <div class="app-truncate" title="{{ $r->editor->name }}">{{ $r->editor->name }}</div>
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('reservations.show', $r) }}">Ver</a>

                                            @can('update', $r)
                                                <a class="btn btn-outline-secondary btn-sm" href="{{ route('reservations.edit', $r) }}">Editar</a>
                                            @endcan

                                            @can('delete', $r)
                                                <form method="POST" action="{{ route('reservations.destroy', $r) }}" onsubmit="return confirm('Excluir este agendamento?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">Excluir</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $reservations->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
@endsection
