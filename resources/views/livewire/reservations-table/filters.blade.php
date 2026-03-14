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

    <div class="lims-grid-toolbar">
        <div class="lims-grid-toolbar-actions">
            @php
                $canManageReservations = auth()->user()?->canManageAgenda() ?? false;
            @endphp

            @can('create', \App\Models\Reservation::class)
                <a href="{{ route('reservations.create') }}" class="btn btn-sm lims-toolbar-btn lims-toolbar-btn-primary">
                    Cadastrar Agendamento
                </a>
            @endcan

            <button type="button" class="btn btn-sm lims-toolbar-btn" wire:click="viewSelected">
                Visualizar
            </button>

            @if ($canManageReservations && $scope !== 'history')
                <button type="button" class="btn btn-sm lims-toolbar-btn" wire:click="editSelected">
                    Editar
                </button>

                <button type="button" class="btn btn-sm lims-toolbar-btn lims-toolbar-btn-danger" wire:click="promptDeleteSelected">
                    Excluir
                </button>
            @endif

            <button type="button" class="btn btn-sm lims-toolbar-btn lims-toolbar-btn-icon-only" wire:click="exportSelection" aria-label="Exportar">
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
            <span class="lims-toolbar-stat">Tela <strong>{{ $scope === 'history' ? 'Historico' : 'Agendamentos' }}</strong></span>
        </div>
    </div>

    <div class="lims-grid-caption">
        Utilize os filtros abaixo do cabecalho para refinar a grade, no mesmo padrao de tabelas operacionais de laboratorio e ERP.
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

                <p class="lims-modal-text">
                    Este registro sera removido da base operacional e nao podera ser recuperado.
                </p>

                <div class="lims-modal-summary">
                    <div><span>Titulo</span><strong data-delete-summary="title">-</strong></div>
                    <div><span>Data</span><strong data-delete-summary="date">-</strong></div>
                    <div><span>Horario</span><strong data-delete-summary="time">-</strong></div>
                    <div><span>Sala</span><strong data-delete-summary="room">-</strong></div>
                </div>
            </div>

            <div class="modal-footer app-modal-footer-compact">
                <button type="button" class="btn btn-outline-secondary btn-sm app-section-btn app-section-btn-light" data-bs-dismiss="modal">
                    Cancelar
                </button>

                <form method="POST" data-delete-form>
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger btn-sm app-delete-confirm-btn">
                        Confirmar exclusao
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
