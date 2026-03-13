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
            class="btn btn-sm app-action-btn app-action-btn-danger js-reservation-delete-trigger"
            data-delete-url="{{ route('reservations.destroy', $reservation) }}"
            data-title="{{ $reservation->title }}"
            data-date="{{ $reservation->date_br }}"
            data-time="{{ $reservation->start_time_br }} - {{ $reservation->end_time_br }}"
            data-room="{{ $reservation->room?->name ?? '-' }}"
        >
            Excluir
        </button>
    @endif
</div>
