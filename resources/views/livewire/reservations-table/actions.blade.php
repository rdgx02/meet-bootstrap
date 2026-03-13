<div class="app-table-actions">
    <a class="btn btn-sm app-action-btn app-action-btn-secondary" href="{{ route('reservations.show', $reservation) }}">
        Ver
    </a>

    @if ($canUpdate)
        <a class="btn btn-sm app-action-btn app-action-btn-secondary" href="{{ route('reservations.edit', $reservation) }}">
            Editar
        </a>
    @endif

    @if ($canDelete)
        <button
            type="button"
            class="btn btn-sm app-action-btn app-action-btn-danger"
            wire:click="deleteReservation({{ $reservation->id }})"
            wire:confirm="Excluir este agendamento?"
        >
            Excluir
        </button>
    @endif
</div>
