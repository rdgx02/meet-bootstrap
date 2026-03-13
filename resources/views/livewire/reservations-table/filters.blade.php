<div class="mb-4">
    @if (session('success'))
        <div class="alert alert-success shadow-sm app-success-alert" role="alert">
            {{ session('success') }}
        </div>
    @endif

    <div class="app-filter-toolbar mb-3">
        <div class="app-filter-toolbar-row">
            <div class="app-filter-group app-filter-group-search">
                <label for="grid_q" class="app-toolbar-label">Buscar</label>
                <input
                    id="grid_q"
                    type="text"
                    class="form-control app-toolbar-input"
                    wire:model.defer="q"
                    placeholder="Titulo, solicitante ou sala..."
                >
            </div>

            <div class="app-filter-group">
                <label for="grid_room_id" class="app-toolbar-label">Sala</label>
                <select id="grid_room_id" class="form-select app-toolbar-input" wire:model.defer="room_id">
                    <option value="">Todas</option>
                    @foreach ($this->rooms() as $room)
                        <option value="{{ $room->id }}">{{ $room->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="app-filter-group">
                <label for="grid_date_from" class="app-toolbar-label">Data inicial</label>
                <input id="grid_date_from" type="date" class="form-control app-toolbar-input" wire:model.defer="date_from">
            </div>

            <div class="app-filter-group">
                <label for="grid_date_to" class="app-toolbar-label">Data final</label>
                <input id="grid_date_to" type="date" class="form-control app-toolbar-input" wire:model.defer="date_to">
            </div>

            <div class="app-filter-actions">
                <button type="button" class="btn btn-primary app-btn-primary app-toolbar-btn" wire:click="applyFilters">
                    Aplicar
                </button>
                <button type="button" class="btn btn-outline-secondary app-toolbar-btn" wire:click="clearFilters">
                    Limpar
                </button>
            </div>
        </div>

        <div class="app-filter-meta">
            <span class="app-stat-badge">Total: {{ $this->total }}</span>
            <span class="app-stat-badge">
                Nesta pagina:
                {{ method_exists($this->records, 'count') ? $this->records->count() : count($this->records) }}
            </span>
        </div>
    </div>
</div>

<div class="modal fade" id="reservationDeleteModal" tabindex="-1" aria-labelledby="reservationDeleteModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content app-delete-modal">
            <div class="modal-header border-0 pb-0">
                <div>
                    <span class="app-modal-kicker">Confirmar exclusao</span>
                    <h2 id="reservationDeleteModalTitle" class="app-modal-title mb-0">Excluir agendamento?</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body pt-3">
                <p class="app-modal-text mb-3">
                    Essa acao remove o agendamento da agenda e nao pode ser desfeita.
                </p>

                <div class="app-modal-summary">
                    <div><span>Titulo</span><strong data-delete-summary="title">-</strong></div>
                    <div><span>Data</span><strong data-delete-summary="date">-</strong></div>
                    <div><span>Horario</span><strong data-delete-summary="time">-</strong></div>
                    <div><span>Sala</span><strong data-delete-summary="room">-</strong></div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>

                <form method="POST" data-delete-form>
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger app-delete-confirm-btn">
                        Excluir agendamento
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
