<div class="lims-grid-toolbar-wrap">
    @php
        $canManageReservations = auth()->user()?->canManageAgenda() ?? false;
    @endphp

    @if (session('success'))
        <div class="alert alert-success lims-inline-alert" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning lims-inline-alert" role="alert">
            {{ session('warning') }}
        </div>
    @endif

    <form
        method="GET"
        action="{{ $scope === 'history' ? route('reservations.history') : route('reservations.index') }}"
        class="lims-manual-filters"
    >
        <div class="lims-manual-filters-grid">
            <input type="hidden" name="per_page" value="{{ $manualFilters['per_page'] ?? $initialPerPage }}">

            <div>
                <label for="toolbar_code" class="form-label fw-semibold mb-1">Código</label>
                <input
                    id="toolbar_code"
                    type="text"
                    name="code"
                    value="{{ $manualFilters['code'] ?? '' }}"
                    class="form-control form-control-sm"
                    placeholder="AG-00001"
                >
            </div>

            <div>
                <label for="toolbar_room" class="form-label fw-semibold mb-1">Sala</label>
                <select id="toolbar_room" name="room_id" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    @foreach ($toolbarRooms as $room)
                        <option value="{{ $room['id'] }}" @selected((string) ($manualFilters['room_id'] ?? '') === (string) $room['id'])>
                            {{ $room['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="toolbar_title" class="form-label fw-semibold mb-1">Título</label>
                <input
                    id="toolbar_title"
                    type="text"
                    name="title"
                    value="{{ $manualFilters['title'] ?? '' }}"
                    class="form-control form-control-sm"
                    placeholder="Título"
                >
            </div>

            <div>
                <label for="toolbar_requester" class="form-label fw-semibold mb-1">Solicitante</label>
                <input
                    id="toolbar_requester"
                    type="text"
                    name="requester"
                    value="{{ $manualFilters['requester'] ?? '' }}"
                    class="form-control form-control-sm"
                    placeholder="Solicitante"
                >
            </div>

            <div>
                <label for="toolbar_date" class="form-label fw-semibold mb-1">Data</label>
                <input
                    id="toolbar_date"
                    type="text"
                    name="date"
                    value="{{ $manualFilters['date'] ?? '' }}"
                    class="js-date-picker form-control form-control-sm"
                    data-min-date=""
                    placeholder="AAAA-MM-DD"
                >
            </div>

            <div>
                <label for="toolbar_start_time" class="form-label fw-semibold mb-1">Início</label>
                <input
                    id="toolbar_start_time"
                    type="text"
                    name="start_time"
                    value="{{ $manualFilters['start_time'] ?? '' }}"
                    class="js-time-picker form-control form-control-sm"
                    placeholder="HH:MM"
                >
            </div>

            <div>
                <label for="toolbar_end_time" class="form-label fw-semibold mb-1">Fim</label>
                <input
                    id="toolbar_end_time"
                    type="text"
                    name="end_time"
                    value="{{ $manualFilters['end_time'] ?? '' }}"
                    class="js-time-picker form-control form-control-sm"
                    placeholder="HH:MM"
                >
            </div>

            <div>
                <label for="toolbar_user_name" class="form-label fw-semibold mb-1">Criado por</label>
                <input
                    id="toolbar_user_name"
                    type="text"
                    name="user_name"
                    value="{{ $manualFilters['user_name'] ?? '' }}"
                    class="form-control form-control-sm"
                    placeholder="Criado por"
                >
            </div>

            <div>
                <label for="toolbar_editor_name" class="form-label fw-semibold mb-1">Editado por</label>
                <input
                    id="toolbar_editor_name"
                    type="text"
                    name="editor_name"
                    value="{{ $manualFilters['editor_name'] ?? '' }}"
                    class="form-control form-control-sm"
                    placeholder="Editado por"
                >
            </div>
        </div>

        <div class="lims-manual-filters-actions">
            <button type="submit" class="btn btn-sm lims-toolbar-btn">Aplicar filtros</button>
            <a href="{{ $scope === 'history' ? route('reservations.history') : route('reservations.index') }}" class="btn btn-sm lims-toolbar-btn">
                Limpar filtros
            </a>
        </div>
    </form>

    <div class="lims-grid-toolbar" data-reservation-toolbar data-table-name="{{ $tableName }}">
        <div class="lims-grid-toolbar-actions">
            @php
                $canManageReservations = auth()->user()?->canManageAgenda() ?? false;
            @endphp

            @can('create', \App\Models\Reservation::class)
                <a href="{{ route('reservations.create') }}" class="btn btn-sm lims-toolbar-btn lims-toolbar-btn-primary">
                    Cadastrar Agendamento
                </a>
            @endcan

            <button
                type="button"
                class="btn btn-sm lims-toolbar-btn js-reservation-bulk-view"
                data-bulk-action="view"
            >
                Visualizar
            </button>

            @if ($canManageReservations && $scope !== 'history')
                <button
                    type="button"
                    class="btn btn-sm lims-toolbar-btn js-reservation-bulk-edit"
                    data-bulk-action="edit"
                >
                    Editar
                </button>

                <button
                    type="button"
                    class="btn btn-sm lims-toolbar-btn lims-toolbar-btn-danger js-reservation-bulk-delete"
                    data-bulk-action="delete"
                >
                    Excluir
                </button>
            @endif

            <button
                type="button"
                class="btn btn-sm lims-toolbar-btn lims-toolbar-btn-icon-only js-reservation-bulk-export"
                aria-label="Exportar"
                data-bulk-action="export"
                data-export-url="{{ route('reservations.export-selected') }}"
            >
                <span class="lims-toolbar-btn-icon-mark" aria-hidden="true">
                    <svg viewBox="0 0 20 20" fill="none">
                        <path d="M10 3v8M6.5 7.5 10 11l3.5-3.5M4 13.5v1A1.5 1.5 0 0 0 5.5 16h9a1.5 1.5 0 0 0 1.5-1.5v-1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
            </button>
        </div>

        <div class="lims-grid-toolbar-stats">
            <span class="lims-toolbar-stat">Registros <strong>{{ $this->total }}</strong></span>
            <span class="lims-toolbar-stat">Selecionados <strong>{{ count($checkboxValues) }}</strong></span>
            <span class="lims-toolbar-stat">Tela <strong>{{ $scope === 'history' ? 'Histórico' : 'Agendamentos' }}</strong></span>
        </div>
    </div>
</div>

<div class="modal fade" id="reservationDeleteModal" tabindex="-1" aria-labelledby="reservationDeleteModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content lims-delete-modal">
            <div class="modal-header">
                <div>
                    <span class="lims-modal-kicker">Confirmação</span>
                    <h2 id="reservationDeleteModalTitle" class="lims-modal-title">Excluir agendamento</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body">
                <div class="app-delete-alert">
                    Essa exclusão remove o agendamento da agenda operacional e do histórico consultivo.
                </div>

                <p class="lims-modal-text" data-delete-message>
                    Este registro será removido da base operacional e não poderá ser recuperado.
                </p>

                <div class="lims-modal-summary" data-delete-single-summary>
                    <div><span>Título</span><strong data-delete-summary="title">-</strong></div>
                    <div><span>Data</span><strong data-delete-summary="date">-</strong></div>
                    <div><span>Horário</span><strong data-delete-summary="time">-</strong></div>
                    <div><span>Sala</span><strong data-delete-summary="room">-</strong></div>
                </div>

                <div class="lims-bulk-delete-summary d-none" data-delete-bulk-summary>
                    <div class="lims-bulk-delete-count">
                        <span>Selecionados</span>
                        <strong data-delete-bulk-count>0 agendamentos</strong>
                    </div>

                    <div class="lims-bulk-delete-list" data-delete-bulk-list></div>
                </div>
            </div>

            <div class="modal-footer app-modal-footer-compact">
                <button type="button" class="btn btn-outline-secondary btn-sm app-section-btn app-section-btn-light" data-bs-dismiss="modal">
                    Cancelar
                </button>

                <form method="POST" data-delete-form data-delete-selected-url="{{ route('reservations.destroy-selected') }}">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="ids" value="">

                    <button type="submit" class="btn btn-danger btn-sm app-delete-confirm-btn">
                        <span data-delete-submit-label>Confirmar exclusão</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
