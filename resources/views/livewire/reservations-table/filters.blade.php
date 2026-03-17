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

            <button type="button" class="btn btn-sm lims-toolbar-btn js-reservation-bulk-view">
                Visualizar
            </button>

            @if ($canManageReservations && $scope !== 'history')
                <button type="button" class="btn btn-sm lims-toolbar-btn js-reservation-bulk-edit">
                    Editar
                </button>

                <button type="button" class="btn btn-sm lims-toolbar-btn lims-toolbar-btn-danger js-reservation-bulk-delete">
                    Excluir
                </button>
            @endif

            <a
                href="#"
                class="btn btn-sm lims-toolbar-btn lims-toolbar-btn-icon-only js-reservation-bulk-export"
                aria-label="Exportar"
                data-export-url="{{ route('reservations.export-selected') }}"
            >
                <span class="lims-toolbar-btn-icon-mark" aria-hidden="true">
                    <svg viewBox="0 0 20 20" fill="none">
                        <path d="M10 3v8M6.5 7.5 10 11l3.5-3.5M4 13.5v1A1.5 1.5 0 0 0 5.5 16h9a1.5 1.5 0 0 0 1.5-1.5v-1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
            </a>
        </div>

        <div class="lims-grid-toolbar-stats">
            <span class="lims-toolbar-stat">Registros <strong>{{ $this->total }}</strong></span>
            <span class="lims-toolbar-stat">Selecionados <strong>{{ count($checkboxValues) }}</strong></span>
            <span class="lims-toolbar-stat">Tela <strong>{{ $scope === 'history' ? 'Historico' : 'Agendamentos' }}</strong></span>
        </div>
    </div>

</div>

<div class="modal fade" id="reservationDeleteModal" tabindex="-1" aria-labelledby="reservationDeleteModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content lims-delete-modal">
            <div class="modal-header">
                <div>
                    <span class="lims-modal-kicker">Confirmacao</span>
                    <h2 id="reservationDeleteModalTitle" class="lims-modal-title">Excluir agendamento</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body">
                <div class="app-delete-alert">
                    Essa exclusao remove o agendamento da agenda operacional e do historico consultivo.
                </div>

                <p class="lims-modal-text" data-delete-message>
                    Este registro sera removido da base operacional e nao podera ser recuperado.
                </p>

                <div class="lims-modal-summary" data-delete-single-summary>
                    <div><span>Titulo</span><strong data-delete-summary="title">-</strong></div>
                    <div><span>Data</span><strong data-delete-summary="date">-</strong></div>
                    <div><span>Horario</span><strong data-delete-summary="time">-</strong></div>
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
                        <span data-delete-submit-label>Confirmar exclusao</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
