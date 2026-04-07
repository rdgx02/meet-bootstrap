@extends('layouts.app')

@section('title', 'Salas')

@section('content')
    <div class="app-module-shell">
        <section class="app-module-header">
            <div>
                <div class="app-module-kicker">Cadastro</div>
                <h1 class="app-module-title">Salas</h1>
                <p class="app-module-note">Gerencie a estrutura física disponível para agendamentos.</p>
            </div>

            @can('create', \App\Models\Room::class)
                <a href="{{ route('rooms.create') }}" class="btn app-btn-primary app-section-btn">
                    Nova sala
                </a>
            @endcan
        </section>

        @if (session('success'))
            <div class="alert alert-success app-success-alert" role="alert">
                {{ session('success') }}
            </div>
        @endif

        <section class="app-subpanel">
            <div class="app-subpanel-head">
                <div>
                    <h2 class="app-subpanel-title">Lista de salas</h2>
                    <p class="app-subpanel-note">Visualização compacta dos ambientes cadastrados no sistema.</p>
                </div>
                <div class="app-subpanel-meta">
                    <span class="app-mini-stat">Total <strong>{{ $rooms->count() }}</strong></span>
                </div>
            </div>

            <div class="app-data-sheet">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 app-record-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Status</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rooms as $room)
                                <tr>
                                    <td class="fw-semibold">{{ $room->name }}</td>
                                    <td>
                                        <span class="app-status-pill {{ $room->is_active ? 'is-active' : 'is-inactive' }}">
                                            {{ $room->is_active ? 'Ativa' : 'Inativa' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="app-inline-actions justify-content-end">
                                            @can('update', $room)
                                                <a href="{{ route('rooms.edit', $room) }}" class="btn btn-sm app-ghost-btn">
                                                    Editar
                                                </a>
                                            @endcan

                                            @can('delete', $room)
                                                <button
                                                    type="button"
                                                    class="btn btn-sm app-ghost-btn app-ghost-btn-danger js-room-delete-trigger"
                                                    data-room-delete-url="{{ route('rooms.destroy', $room) }}"
                                                    data-room-name="{{ $room->name }}"
                                                    data-room-status="{{ $room->is_active ? 'Ativa' : 'Inativa' }}"
                                                >
                                                    Excluir
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-body-secondary py-5">
                                        Nenhuma sala cadastrada.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <div class="modal fade" id="roomDeleteModal" tabindex="-1" aria-labelledby="roomDeleteModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content lims-delete-modal">
                    <div class="modal-header">
                        <div>
                            <span class="lims-modal-kicker">Ação permanente</span>
                            <h2 id="roomDeleteModalTitle" class="lims-modal-title">Excluir sala</h2>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body">
                        <div class="app-delete-alert">
                            Essa exclusão remove a sala do cadastro e pode afetar consultas futuras do ambiente.
                        </div>

                        <p class="lims-modal-text mb-0">
                            Confirme a operação somente se essa sala não precisar mais aparecer no cadastro administrativo.
                        </p>

                        <div class="lims-modal-summary mt-3">
                            <div><span>Sala</span><strong data-room-delete-summary="name">-</strong></div>
                            <div><span>Status atual</span><strong data-room-delete-summary="status">-</strong></div>
                        </div>
                    </div>

                    <div class="modal-footer app-modal-footer-compact">
                        <button type="button" class="btn btn-outline-secondary btn-sm app-section-btn app-section-btn-light" data-bs-dismiss="modal">
                            Cancelar
                        </button>

                        <form method="POST" data-room-delete-form>
                            @csrf
                            @method('DELETE')

                            <button type="submit" class="btn btn-danger btn-sm app-delete-confirm-btn">
                                Excluir sala
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
