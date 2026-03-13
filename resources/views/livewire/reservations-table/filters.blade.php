<div class="mb-4">
    @if (session('success'))
        <div class="alert alert-success shadow-sm app-success-alert" role="alert">
            {{ session('success') }}
        </div>
    @endif

    <div class="app-card app-filter-card p-4 mb-3">
        <div class="app-filter-head">
            <div>
                <h2 class="app-filter-title">Filtrar agenda</h2>
                <p class="app-filter-text mb-0">Refine por texto, sala e intervalo de datas.</p>
            </div>
        </div>

        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-4">
                <label for="grid_q" class="form-label">Buscar</label>
                <input
                    id="grid_q"
                    type="text"
                    class="form-control"
                    wire:model.defer="q"
                    placeholder="Titulo, solicitante ou sala..."
                >
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <label for="grid_room_id" class="form-label">Sala</label>
                <select id="grid_room_id" class="form-select" wire:model.defer="room_id">
                    <option value="">Todas</option>
                    @foreach ($this->rooms() as $room)
                        <option value="{{ $room->id }}">{{ $room->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-6 col-lg-2">
                <label for="grid_date_from" class="form-label">Data inicial</label>
                <input id="grid_date_from" type="date" class="form-control" wire:model.defer="date_from">
            </div>

            <div class="col-12 col-md-6 col-lg-2">
                <label for="grid_date_to" class="form-label">Data final</label>
                <input id="grid_date_to" type="date" class="form-control" wire:model.defer="date_to">
            </div>

            <div class="col-12 col-lg-1 d-flex justify-content-lg-end">
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-primary app-btn-primary" wire:click="applyFilters">
                        Aplicar
                    </button>
                    <button type="button" class="btn btn-outline-secondary" wire:click="clearFilters">
                        Limpar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <span class="badge rounded-pill app-stat-badge px-3 py-2">Total: {{ $this->total }}</span>
        <span class="badge rounded-pill app-stat-badge px-3 py-2">
            Nesta pagina:
            {{ method_exists($this->records, 'count') ? $this->records->count() : count($this->records) }}
        </span>
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
