<div class="mb-4">
    @if (session('success'))
        <div class="alert alert-success shadow-sm" role="alert">
            {{ session('success') }}
        </div>
    @endif

    <div class="app-card app-filter-card p-4 mb-3">
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
