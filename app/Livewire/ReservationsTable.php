<?php

namespace App\Livewire;

use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\Facades\Rule;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReservationsTable extends PowerGridComponent
{
    use AuthorizesRequests;

    public string $tableName = 'reservations-upcoming-table';

    public string $scope = 'upcoming';

    public int $initialPerPage = 20;

    public function mount(string $scope = 'upcoming', array $filters = []): void
    {
        $this->scope = $scope;
        $this->tableName = $scope === 'history'
            ? 'reservations-history-table'
            : 'reservations-upcoming-table';

        $this->initialPerPage = $this->resolvePerPage($filters['per_page'] ?? 20);
        $this->sortField = 'date';
        $this->sortDirection = $scope === 'history' ? 'desc' : 'asc';

        parent::mount();
    }

    public function setUp(): array
    {
        $this->showCheckBox('id');

        return [
            PowerGrid::header()
                ->includeViewOnTop('livewire.reservations-table.filters')
                ->withoutLoading(),
            PowerGrid::footer()
                ->showPerPage($this->initialPerPage, [20, 50, 100])
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

        return $query;
    }

    public function relationSearch(): array
    {
        return [
            'room' => ['name'],
            'user' => ['name'],
            'editor' => ['name'],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('code', fn (Reservation $reservation): string => 'AG-' . str_pad((string) $reservation->id, 5, '0', STR_PAD_LEFT))
            ->add('date')
            ->add('date_br', fn (Reservation $reservation): string => $reservation->date_br)
            ->add('start_time')
            ->add('start_time_br', fn (Reservation $reservation): string => $reservation->start_time_br)
            ->add('end_time')
            ->add('end_time_br', fn (Reservation $reservation): string => $reservation->end_time_br)
            ->add('room_name', fn (Reservation $reservation): string => e($reservation->room?->name ?? '-'))
            ->add('title', fn (Reservation $reservation): string => e($reservation->title))
            ->add('requester', fn (Reservation $reservation): string => e($reservation->requester))
            ->add('user_name', fn (Reservation $reservation): string => e($reservation->user?->name ?? '-'))
            ->add('editor_name', fn (Reservation $reservation): string => e($reservation->editor?->name ?? '-'))
            ->add('row_state', fn (Reservation $reservation): string => $this->rowState($reservation));
    }

    public function columns(): array
    {
        return [
            Column::make('Codigo', 'code', 'id')
                ->sortable()
                ->headerAttribute('app-col-code-head', 'min-width: 8.75rem;')
                ->bodyAttribute('app-col-code'),

            Column::make('Sala', 'room_name', 'room.name')
                ->sortable()
                ->searchable()
                ->headerAttribute('app-col-room-head', 'min-width: 6rem;')
                ->bodyAttribute('app-col-room'),

            Column::make('Titulo', 'title', 'title')
                ->sortable()
                ->searchable()
                ->headerAttribute('app-col-title-head', 'min-width: 13rem;')
                ->bodyAttribute('app-col-title'),

            Column::make('Solicitante', 'requester', 'requester')
                ->sortable()
                ->searchable()
                ->headerAttribute('app-col-requester-head', 'min-width: 9rem;')
                ->bodyAttribute('app-col-requester'),

            Column::make('Data', 'date_br', 'date')
                ->sortable()
                ->headerAttribute('app-col-date-head', 'min-width: 8.5rem;')
                ->bodyAttribute('app-col-date'),

            Column::make('Inicio', 'start_time_br', 'start_time')
                ->sortable()
                ->headerAttribute('app-col-time-head', 'min-width: 6.5rem;')
                ->bodyAttribute('app-col-time'),

            Column::make('Fim', 'end_time_br', 'end_time')
                ->sortable()
                ->headerAttribute('app-col-time-head', 'min-width: 6.5rem;')
                ->bodyAttribute('app-col-time'),

            Column::make('Criado por', 'user_name')
                ->headerAttribute('app-col-user-head', 'min-width: 8.5rem;')
                ->bodyAttribute('app-col-user'),

            Column::make('Editado por', 'editor_name')
                ->headerAttribute('app-col-user-head', 'min-width: 8.5rem;')
                ->bodyAttribute('app-col-user'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::inputText('code', 'id')
                ->operators(['contains', 'is'])
                ->placeholder('Codigo')
                ->builder(function (Builder $query, array $values): void {
                    $value = preg_replace('/\D+/', '', (string) ($values['value'] ?? ''));

                    if ($value === '' || $value === null) {
                        return;
                    }

                    if (($values['selected'] ?? 'contains') === 'is') {
                        $query->where('id', (int) $value);

                        return;
                    }

                    $query->where('id', 'like', '%' . $value . '%');
                }),

            Filter::select('room_name', 'room_id')
                ->dataSource($this->rooms()->map(fn (Room $room): array => [
                    'id' => $room->id,
                    'name' => $room->name,
                ])->all())
                ->optionValue('id')
                ->optionLabel('name'),

            Filter::inputText('title', 'title')
                ->operators(['contains'])
                ->placeholder('Titulo'),

            Filter::inputText('requester', 'requester')
                ->operators(['contains'])
                ->placeholder('Solicitante'),

            Filter::datepicker('date_br', 'date')
                ->params([
                    'enableTime' => false,
                    'dateFormat' => 'Y-m-d',
                    'altInput' => true,
                    'altFormat' => 'd/m/Y',
                    'mode' => 'single',
                    'monthSelectorType' => 'static',
                    'prevArrow' => '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"/></svg>',
                    'nextArrow' => '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M7.5 15L12.5 10L7.5 5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"/></svg>',
                ]),

            Filter::inputText('start_time_br', 'start_time')
                ->operators(['contains'])
                ->placeholder('HH:MM'),

            Filter::inputText('end_time_br', 'end_time')
                ->operators(['contains'])
                ->placeholder('HH:MM'),

            Filter::inputText('user_name')
                ->operators(['contains'])
                ->placeholder('Criado por')
                ->filterRelation('user', 'name'),

            Filter::inputText('editor_name')
                ->operators(['contains'])
                ->placeholder('Editado por')
                ->filterRelation('editor', 'name'),
        ];
    }

    public function actionRules($row): array
    {
        return [
            Rule::rows()
                ->when(fn () => $this->rowState($row) === 'confirmed')
                ->setAttribute('class', 'app-grid-row app-grid-row-confirmed'),
            Rule::rows()
                ->when(fn () => $this->rowState($row) === 'reserved')
                ->setAttribute('class', 'app-grid-row app-grid-row-reserved'),
            Rule::rows()
                ->when(fn () => $this->rowState($row) === 'archived')
                ->setAttribute('class', 'app-grid-row app-grid-row-archived'),
        ];
    }

    public function exportSelection(): StreamedResponse
    {
        $selectedIds = collect($this->checkboxValues)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->values();

        $reservations = Reservation::query()
            ->with(['room', 'user', 'editor'])
            ->when($selectedIds->isNotEmpty(), fn (Builder $query) => $query->whereIn('id', $selectedIds))
            ->when($selectedIds->isEmpty(), function (Builder $query): void {
                $today = now()->toDateString();
                $currentTime = now()->format('H:i:s');

                if ($this->scope === 'history') {
                    $query->where(function (Builder $historyQuery) use ($today, $currentTime): void {
                        $historyQuery->whereDate('date', '<', $today)
                            ->orWhere(function (Builder $sameDayQuery) use ($today, $currentTime): void {
                                $sameDayQuery->whereDate('date', '=', $today)
                                    ->where('end_time', '<=', $currentTime);
                            });
                    });

                    return;
                }

                $query->where(function (Builder $upcomingQuery) use ($today, $currentTime): void {
                    $upcomingQuery->whereDate('date', '>', $today)
                        ->orWhere(function (Builder $sameDayQuery) use ($today, $currentTime): void {
                            $sameDayQuery->whereDate('date', '=', $today)
                                ->where('end_time', '>', $currentTime);
                        });
                });
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $filename = 'agendamentos-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($reservations): void {
            $output = fopen('php://output', 'w');

            fputcsv($output, ['Codigo', 'Sala', 'Titulo', 'Solicitante', 'Data', 'Inicio', 'Fim', 'Criado por', 'Editado por'], ';');

            foreach ($reservations as $reservation) {
                fputcsv($output, [
                    'AG-' . str_pad((string) $reservation->id, 5, '0', STR_PAD_LEFT),
                    $reservation->room?->name ?? '-',
                    $reservation->title,
                    $reservation->requester,
                    $reservation->date_br,
                    $reservation->start_time_br,
                    $reservation->end_time_br,
                    $reservation->user?->name ?? '-',
                    $reservation->editor?->name ?? '-',
                ], ';');
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function viewSelected()
    {
        $reservation = $this->selectedReservationForSingleAction('visualizar');

        if (! $reservation instanceof Reservation) {
            return null;
        }

        return redirect()->route('reservations.show', $reservation);
    }

    public function editSelected()
    {
        $reservation = $this->selectedReservationForSingleAction('editar');

        if (! $reservation instanceof Reservation) {
            return null;
        }

        if (! (Auth::user()?->can('update', $reservation) ?? false)) {
            session()->flash('warning', 'O agendamento selecionado nao pode mais ser editado.');

            return null;
        }

        return redirect()->route('reservations.edit', $reservation);
    }

    public function promptDeleteSelected(): void
    {
        $reservation = $this->selectedReservationForSingleAction('excluir');

        if (! $reservation instanceof Reservation) {
            return;
        }

        if (! (Auth::user()?->can('delete', $reservation) ?? false)) {
            session()->flash('warning', 'O agendamento selecionado nao pode mais ser excluido.');

            return;
        }

        $this->dispatch(
            'reservation-delete-requested',
            deleteUrl: route('reservations.destroy', $reservation),
            title: $reservation->title,
            date: $reservation->date_br,
            time: $reservation->start_time_br . ' - ' . $reservation->end_time_br,
            room: $reservation->room?->name ?? '-'
        );
    }

    public function refreshDataset(): void
    {
        $this->checkboxAll = false;
        $this->checkboxValues = [];
        $this->resetPage(data_get($this->setUp, 'footer.pageName'));
    }

    public function rooms()
    {
        return Room::active()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function noDataLabel(): string
    {
        return 'Nenhum agendamento corresponde aos filtros informados.';
    }

    private function resolvePerPage(mixed $value): int
    {
        $perPage = (int) $value;

        return in_array($perPage, [20, 50, 100], true) ? $perPage : 20;
    }

    private function rowState(Reservation $reservation): string
    {
        if ($this->scope === 'history') {
            return 'archived';
        }

        return $reservation->date === now()->toDateString() ? 'confirmed' : 'reserved';
    }

    private function selectedReservationForSingleAction(string $actionLabel): ?Reservation
    {
        $selectedIds = collect($this->checkboxValues)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->values();

        if ($selectedIds->isEmpty()) {
            session()->flash('warning', sprintf('Selecione um agendamento para %s.', $actionLabel));

            return null;
        }

        if ($selectedIds->count() > 1) {
            session()->flash('warning', sprintf('Selecione apenas um agendamento para %s.', $actionLabel));

            return null;
        }

        return Reservation::query()
            ->with('room')
            ->find($selectedIds->first());
    }
}
