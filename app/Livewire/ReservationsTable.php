<?php

namespace App\Livewire;

use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\Facades\Rule;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class ReservationsTable extends PowerGridComponent
{
    use AuthorizesRequests;

    public string $tableName = 'reservations-upcoming-table';

    public string $scope = 'upcoming';

    public string $q = '';

    public ?string $room_id = null;

    public ?string $date_from = null;

    public ?string $date_to = null;

    public int $initialPerPage = 10;

    public function mount(string $scope = 'upcoming', array $filters = []): void
    {
        $this->scope = $scope;
        $this->tableName = $scope === 'history'
            ? 'reservations-history-table'
            : 'reservations-upcoming-table';

        $this->q = trim((string) ($filters['q'] ?? ''));
        $this->room_id = filled($filters['room_id'] ?? null) ? (string) $filters['room_id'] : null;
        $this->date_from = filled($filters['date_from'] ?? null) ? (string) $filters['date_from'] : null;
        $this->date_to = filled($filters['date_to'] ?? null) ? (string) $filters['date_to'] : null;
        $this->initialPerPage = $this->resolvePerPage($filters['per_page'] ?? 10);
        $this->sortField = 'date';
        $this->sortDirection = $scope === 'history' ? 'desc' : 'asc';

        parent::mount();
    }

    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->includeViewOnTop('livewire.reservations-table.filters')
                ->withoutLoading(),
            PowerGrid::footer()
                ->showPerPage($this->initialPerPage, [10, 20, 50, 100])
                ->showRecordCount('short')
                ->pageName($this->scope === 'history' ? 'historyPage' : 'reservationsPage'),
        ];
    }

    public function datasource(): Builder
    {
        $today = now()->toDateString();
        $currentTime = now()->format('H:i:s');

        $query = Reservation::query()
            ->with(['room', 'user', 'editor']);

        if ($this->scope === 'history') {
            $query->where(function (Builder $historyQuery) use ($today, $currentTime): void {
                $historyQuery->whereDate('date', '<', $today)
                    ->orWhere(function (Builder $sameDayQuery) use ($today, $currentTime): void {
                        $sameDayQuery->whereDate('date', '=', $today)
                            ->where('end_time', '<=', $currentTime);
                    });
            });
        } else {
            $query->where(function (Builder $upcomingQuery) use ($today, $currentTime): void {
                $upcomingQuery->whereDate('date', '>', $today)
                    ->orWhere(function (Builder $sameDayQuery) use ($today, $currentTime): void {
                        $sameDayQuery->whereDate('date', '=', $today)
                            ->where('end_time', '>', $currentTime);
                    });
            });
        }

        if (filled($this->room_id)) {
            $query->where('room_id', (int) $this->room_id);
        }

        if (filled($this->date_from)) {
            $query->whereDate('date', '>=', $this->date_from);
        }

        if (filled($this->date_to)) {
            $query->whereDate('date', '<=', $this->date_to);
        }

        if ($this->q !== '') {
            $query->where(function (Builder $searchQuery): void {
                $searchQuery->where('title', 'like', "%{$this->q}%")
                    ->orWhere('requester', 'like', "%{$this->q}%")
                    ->orWhereHas('room', function (Builder $roomQuery): void {
                        $roomQuery->where('name', 'like', "%{$this->q}%");
                    });
            });
        }

        return $query;
    }

    public function relationSearch(): array
    {
        return [
            'room' => ['name'],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('date')
            ->add('date_br', fn (Reservation $reservation): string => $reservation->date_br)
            ->add('start_time')
            ->add('start_time_br', fn (Reservation $reservation): string => $reservation->start_time_br)
            ->add('end_time')
            ->add('end_time_br', fn (Reservation $reservation): string => $reservation->end_time_br)
            ->add('room_name', fn (Reservation $reservation): string => e($reservation->room?->name ?? '-'))
            ->add('room_badge', fn (Reservation $reservation): string => sprintf(
                '<span class="badge rounded-pill app-room-badge">%s</span>',
                e($reservation->room?->name ?? '-')
            ))
            ->add('title')
            ->add('title_truncate', fn (Reservation $reservation): string => sprintf(
                '<div class="app-truncate" title="%s">%s</div>',
                e($reservation->title),
                e($reservation->title)
            ))
            ->add('requester')
            ->add('requester_truncate', fn (Reservation $reservation): string => sprintf(
                '<div class="app-truncate" title="%s">%s</div>',
                e($reservation->requester),
                e($reservation->requester)
            ))
            ->add('user_name', fn (Reservation $reservation): string => e($reservation->user?->name ?? '-'))
            ->add('user_chip', fn (Reservation $reservation): string => $this->userChip($reservation->user?->name))
            ->add('editor_name', fn (Reservation $reservation): string => e($reservation->editor?->name ?? '-'))
            ->add('editor_chip', fn (Reservation $reservation): string => $this->userChip($reservation->editor?->name))
            ->add('row_class', fn (Reservation $reservation): string => Auth::id() === $reservation->user_id ? 'row-mine' : '');
    }

    public function columns(): array
    {
        return [
            Column::make('Data', 'date_br', 'date')
                ->sortable()
                ->searchable()
                ->bodyAttribute('fw-semibold'),

            Column::make('Inicio', 'start_time_br', 'start_time')
                ->sortable(),

            Column::make('Fim', 'end_time_br', 'end_time')
                ->sortable(),

            Column::make('Sala', 'room_badge', 'room.name')
                ->searchable(),

            Column::make('Titulo', 'title_truncate', 'title')
                ->searchable()
                ->sortable(),

            Column::make('Solicitante', 'requester_truncate', 'requester')
                ->searchable()
                ->sortable(),

            Column::make('Criado por', 'user_chip')
                ->bodyAttribute('text-nowrap'),

            Column::make('Editado por', 'editor_chip')
                ->bodyAttribute('text-nowrap'),

            Column::action('Acoes')
                ->bodyAttribute('text-nowrap'),
        ];
    }

    public function actions(Reservation $row): array
    {
        $actions = [
            Button::add('view')
                ->slot('Ver')
                ->class('btn btn-outline-secondary btn-sm')
                ->route('reservations.show', [$row]),
        ];

        if (Auth::user()?->can('update', $row)) {
            $actions[] = Button::add('edit')
                ->slot('Editar')
                ->class('btn btn-outline-secondary btn-sm')
                ->route('reservations.edit', [$row]);
        }

        if (Auth::user()?->can('delete', $row)) {
            $actions[] = Button::add('delete')
                ->slot('Excluir')
                ->class('btn btn-outline-danger btn-sm')
                ->confirm('Excluir este agendamento?')
                ->call('deleteReservation', ['rowId' => $row->id]);
        }

        return $actions;
    }

    public function actionsFromView(Reservation $row): string
    {
        return view('livewire.reservations-table.actions', [
            'reservation' => $row,
            'canUpdate' => Auth::user()?->can('update', $row) ?? false,
            'canDelete' => Auth::user()?->can('delete', $row) ?? false,
        ])->render();
    }

    public function actionRules($row): array
    {
        return [
            Rule::rows()
                ->when(fn () => Auth::id() === $row->user_id)
                ->setAttribute('class', 'row-mine'),
        ];
    }

    public function deleteReservation(int $rowId): void
    {
        $reservation = Reservation::query()->findOrFail($rowId);

        $this->authorize('delete', $reservation);

        $reservation->delete();

        session()->flash('success', 'Agendamento excluído com sucesso!');
    }

    public function applyFilters(): void
    {
        $this->gotoPage(1, data_get($this->setUp, 'footer.pageName'));
    }

    public function clearFilters(): void
    {
        $this->q = '';
        $this->room_id = null;
        $this->date_from = null;
        $this->date_to = null;

        $this->gotoPage(1, data_get($this->setUp, 'footer.pageName'));
    }

    public function rooms()
    {
        return Room::active()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function noDataLabel(): string
    {
        return 'Nenhum agendamento encontrado.';
    }

    private function resolvePerPage(mixed $value): int
    {
        $perPage = (int) $value;

        return in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 10;
    }

    private function userChip(?string $name): string
    {
        if (blank($name)) {
            return '-';
        }

        $initial = strtoupper(mb_substr($name, 0, 1));

        return sprintf(
            '<div class="app-user-chip"><div class="app-avatar">%s</div><div class="app-truncate" title="%s">%s</div></div>',
            e($initial),
            e($name),
            e($name)
        );
    }
}
