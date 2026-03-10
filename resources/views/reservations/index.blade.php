@extends('layouts.app')

@section('title', $title ?? 'Agendamentos')

@section('content')
    <style>
        .page { max-width: 1100px; margin: 0 auto; }
        .header { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom: 14px; }
        .title { margin: 0; font-size: 22px; }
        .subtitle { margin: 6px 0 0; color:#4b5563; font-size: 14px; }

        .btn {
            display:inline-flex; align-items:center; justify-content:center;
            padding: 8px 12px; border-radius: 8px; border: 1px solid #d0d7de;
            background: #fff; text-decoration:none; color:#111; cursor:pointer;
            font-weight: 600;
        }
        .btn:hover { background:#f6f8fa; }
        .btn-primary { background:#0b5fff; border-color:#0b5fff; color:#fff; }
        .btn-primary:hover { filter: brightness(0.95); background:#0b5fff; }
        .btn-danger { background:#d1242f; border-color:#d1242f; color:#fff; }
        .btn-danger:hover { filter: brightness(0.95); background:#d1242f; }

        /* botões pequenos e neutros (mais "sistema" e menos link) */
        .btn-sm { padding: 7px 10px; border-radius: 10px; font-size: 13px; font-weight: 700; }
        .btn-ghost { background:#fff; border-color:#e5e7eb; }
        .btn-ghost:hover { background:#f3f4f6; }

        .alert {
            border-radius: 10px; padding: 12px 14px; margin: 12px 0 14px;
            border: 1px solid #b7ebc6; background: #eafff1; color:#0f5132;
        }

        .card {
            border:1px solid #e5e7eb; border-radius: 12px; padding: 14px;
            background:#fff; box-shadow: 0 1px 2px rgba(0,0,0,.04);
            margin-bottom: 14px;
        }

        .filters { display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; }
        .field { display:flex; flex-direction:column; gap:6px; min-width: 180px; }
        .field label { font-weight:700; font-size: 13px; color:#374151; }
        .input, select {
            padding: 9px 10px; border-radius: 10px; border: 1px solid #d0d7de;
            background: #fff; outline: none;
        }
        .input:focus, select:focus { border-color:#0b5fff; box-shadow: 0 0 0 3px rgba(11,95,255,.15); }
        .meta { color:#4b5563; font-size: 14px; margin: 10px 0; }

        .table-wrap { overflow:auto; border-radius: 12px; border:1px solid #e5e7eb; }
        table { width:100%; border-collapse: collapse; min-width: 980px; background:#fff; }
        thead th {
            text-align:left; font-size: 12px; letter-spacing:.02em;
            color:#374151; background:#f9fafb; border-bottom:1px solid #e5e7eb;
            padding: 10px 12px; white-space: nowrap;
        }
        tbody td { padding: 12px; border-bottom:1px solid #eef2f7; vertical-align: middle; }

        /* refinamento tabela (mais confortável) */
        tbody tr:nth-child(even) { background:#fcfdff; }
        tbody tr:hover { background:#f6f8ff; }

        .actions { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }

        .pill {
            display:inline-flex; align-items:center; padding: 4px 10px;
            border-radius: 999px; background:#f3f4f6; border:1px solid #e5e7eb;
            font-size: 12px; font-weight: 700; color:#111;
        }

        /* destaque + badges + truncamento */
        .text-strong { font-weight: 800; color:#111; }

        .badge {
            display:inline-flex; align-items:center; justify-content:center;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            font-size: 12px;
            font-weight: 800;
            color:#111;
        }

        .truncate {
            max-width: 260px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .footer { margin-top: 12px; display:flex; justify-content:center; }

        /* Criado/Editado por (avatar + nome) */
        .user-cell {
            display:flex;
            align-items:center;
            gap:10px;
        }

        .avatar {
            width:28px;
            height:28px;
            border-radius:50%;
            background:#0b5fff;
            color:#fff;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:13px;
            font-weight:800;
            flex-shrink:0;
        }

        /* ===== Destaque: agendamento "MEU" ===== */
        tbody tr.row-mine {
            background: rgba(11,95,255,.07) !important;
        }
        tbody tr.row-mine:hover {
            background: rgba(11,95,255,.12) !important;
        }
    </style>

    <div class="page">
        <div class="header">
            <div>
                <h2 class="title">{{ $title ?? 'Agendamentos' }}</h2>
                <p class="subtitle">{{ $subtitle ?? '' }}</p>
            </div>

            @if (($scope ?? 'upcoming') === 'upcoming')
                @can('create', \App\Models\Reservation::class)
                <a class="btn btn-primary" href="{{ route('reservations.create') }}">+ Novo Agendamento</a>
                @endcan
            @endif
        </div>

        @if (session('success'))
            <div class="alert">
                {{ session('success') }}
            </div>
        @endif

        <div class="card">
            <form method="GET" action="{{ route($filterRoute ?? 'reservations.index') }}" class="filters">
                {{-- Busca --}}
                <div class="field" style="min-width: 260px;">
                    <label for="q">Buscar</label>
                    <input
                        type="text"
                        id="q"
                        name="q"
                        class="input"
                        value="{{ request('q') }}"
                        placeholder="Título, solicitante ou sala..."
                    >
                </div>

                <div class="field">
                    <label for="room_id">Sala</label>
                    <select name="room_id" id="room_id">
                        <option value="">Todas</option>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->id }}"
                                {{ request('room_id') == $room->id ? 'selected' : '' }}>
                                {{ $room->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="date_from">Data inicial</label>
                    <input
                        type="text"
                        id="date_from"
                        name="date_from"
                        class="input js-date-picker"
                        value="{{ request('date_from') }}"
                        placeholder="dd/mm/aaaa"
                    >
                </div>

                <div class="field">
                    <label for="date_to">Data final</label>
                    <input
                        type="text"
                        id="date_to"
                        name="date_to"
                        class="input js-date-picker"
                        value="{{ request('date_to') }}"
                        placeholder="dd/mm/aaaa"
                    >
                </div>

                <div class="field">
                    <label for="per_page">Por página</label>
                    <select id="per_page" name="per_page">
                        @foreach ([10, 20, 50, 100] as $n)
                            <option value="{{ $n }}" {{ (int)request('per_page', 10) === $n ? 'selected' : '' }}>
                                {{ $n }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field" style="min-width: 260px;">
                    <label>&nbsp;</label>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <button type="submit" class="btn">Aplicar</button>
                        <a class="btn" href="{{ route($filterRoute ?? 'reservations.index', ['per_page' => request('per_page', 10)]) }}">
                            Limpar filtros
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="meta">
            <span class="pill">Total: {{ $reservations->total() }}</span>
            <span style="margin-left:10px;" class="pill">Nesta página: {{ $reservations->count() }}</span>
        </div>

        @if ($reservations->count() === 0)
            <div class="card">
                <p style="margin:0; color:#374151;">Nenhum agendamento encontrado.</p>
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Início</th>
                            <th>Fim</th>
                            <th>Sala</th>
                            <th>Título</th>
                            <th>Solicitante</th>
                            <th>Criado por</th>
                            <th>Editado por</th>
                            <th style="width: 280px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reservations as $r)
                            <tr class="{{ auth()->id() === $r->user_id ? 'row-mine' : '' }}">
                                <td class="text-strong">{{ $r->date_br }}</td>
                                <td>{{ $r->start_time_br }}</td>
                                <td>{{ $r->end_time_br }}</td>
                                <td><span class="badge">{{ $r->room?->name }}</span></td>

                                <td>
                                    <div class="truncate" title="{{ $r->title }}">{{ $r->title }}</div>
                                </td>

                                <td>
                                    <div class="truncate" title="{{ $r->requester }}">{{ $r->requester }}</div>
                                </td>

                                {{-- Criado por --}}
                                <td>
                                    @if($r->user)
                                        <div class="user-cell">
                                            <div class="avatar">
                                                {{ strtoupper(substr($r->user->name, 0, 1)) }}
                                            </div>

                                            <div class="truncate" title="{{ $r->user->name }}">
                                                {{ $r->user->name }}
                                            </div>
                                        </div>
                                    @else
                                        —
                                    @endif
                                </td>

                                {{-- Editado por --}}
                                <td>
                                    @if($r->editor)
                                        <div class="user-cell">
                                            <div class="avatar">
                                                {{ strtoupper(substr($r->editor->name, 0, 1)) }}
                                            </div>

                                            <div class="truncate" title="{{ $r->editor->name }}">
                                                {{ $r->editor->name }}
                                            </div>
                                        </div>
                                    @else
                                        —
                                    @endif
                                </td>

                                <td>
                                    <div class="actions">
                                        <a class="btn btn-sm btn-ghost" href="{{ route('reservations.show', $r) }}">Ver</a>

                                        @can('update', $r)
                                            <a class="btn btn-sm btn-ghost" href="{{ route('reservations.edit', $r) }}">Editar</a>
                                        @endcan

                                        @can('delete', $r)
                                            <form method="POST"
                                                  action="{{ route('reservations.destroy', $r) }}"
                                                  onsubmit="return confirm('Excluir este agendamento?');"
                                                  style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="footer" style="margin-top:18px;">
                {{ $reservations->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
@endsection
