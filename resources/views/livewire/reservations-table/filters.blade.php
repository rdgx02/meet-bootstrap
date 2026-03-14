<div class="lims-grid-toolbar-wrap">
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
            @can('create', \App\Models\Reservation::class)
                <a href="{{ route('reservations.create') }}" class="btn btn-sm lims-toolbar-btn lims-toolbar-btn-primary">
                    Cadastrar Agendamento
                </a>
            @endcan

            <button type="button" class="btn btn-sm lims-toolbar-btn" wire:click="bulkEditSelected">
                Editar em massa
            </button>

            <button type="button" class="btn btn-sm lims-toolbar-btn" wire:click="exportSelection">
                Exportar
            </button>

            <button type="button" class="btn btn-sm lims-toolbar-btn" wire:click="cancelSelected">
                Cancelar/Reativar
            </button>

            <button type="button" class="btn btn-sm lims-toolbar-btn" wire:click="refreshDataset">
                Atualizar
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

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                    Fechar
                </button>

                <form method="POST" data-delete-form>
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger btn-sm">
                        Confirmar exclusao
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
