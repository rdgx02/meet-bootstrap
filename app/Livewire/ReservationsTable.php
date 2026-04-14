<?php

namespace App\Livewire;

use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\Facades\Rule;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class ReservationsTable extends PowerGridComponent
{
    use AuthorizesRequests;

    public string $tableName = 'reservations-upcoming-table';

    public string $scope = 'upcoming';

    public int $initialPerPage = 20;

    public array $manualFilters = [];

    public array $toolbarRooms = [];

    public function mount(string $scope = 'upcoming', array $filters = []): void
    {
        $this->scope = $scope;
        $this->tableName = $scope === 'history'
            ? 'reservations-history-table'
            : 'reservations-upcoming-table';

        $this->filters = [
            'select' => [],
            'input_text' => [],
            'input_text_options' => [],
            'date' => [],
            'boolean' => [],
            'number' => [],
        ];
        $this->manualFilters = $filters;
        $this->initialPerPage = $this->resolvePerPage($filters['per_page'] ?? 20);
        $this->toolbarRooms = $this->rooms()
            ->map(fn (Room $room): array => [
                'id' => $room->id,
                'name' => $room->name,
            ])
            ->all();
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

        $this->applyManualFilters($query);

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
            Column::make('Código', 'code', 'id')
                ->sortable()
                ->headerAttribute('app-col-code-head', 'min-width: 8.75rem;')
                ->bodyAttribute('app-col-code'),

            Column::make('Sala', 'room_name', 'room.name')
                ->sortable()
                ->searchable()
                ->headerAttribute('app-col-room-head', 'min-width: 6rem;')
                ->bodyAttribute('app-col-room'),

            Column::make('Título', 'title', 'title')
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

            Column::make('Início', 'start_time_br', 'start_time')
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
                ->placeholder('Código')
                ->builder(function (Builder $query, array $values): void {
                    $value = $this->normalizeReservationCodeFilter($values['value'] ?? null);

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
                ->placeholder('Título'),

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

    private function applyManualFilters(Builder $query): void
    {
        $code = $this->normalizeReservationCodeFilter($this->manualFilters['code'] ?? null);

        if ($code !== null && $code !== '') {
            $query->where('id', 'like', '%' . $code . '%');
        }

        if (filled($this->manualFilters['room_id'] ?? null)) {
            $query->where('room_id', (int) $this->manualFilters['room_id']);
        }

        if (filled($this->manualFilters['title'] ?? null)) {
            $query->where('title', 'like', '%' . trim((string) $this->manualFilters['title']) . '%');
        }

        if (filled($this->manualFilters['requester'] ?? null)) {
            $query->where('requester', 'like', '%' . trim((string) $this->manualFilters['requester']) . '%');
        }

        if (filled($this->manualFilters['date'] ?? null)) {
            $query->whereDate('date', (string) $this->manualFilters['date']);
        }

        if (filled($this->manualFilters['start_time'] ?? null)) {
            $query->where('start_time', 'like', trim((string) $this->manualFilters['start_time']) . '%');
        }

        if (filled($this->manualFilters['end_time'] ?? null)) {
            $query->where('end_time', 'like', trim((string) $this->manualFilters['end_time']) . '%');
        }

        if (filled($this->manualFilters['user_name'] ?? null)) {
            $query->whereHas('user', function (Builder $userQuery): void {
                $userQuery->where('name', 'like', '%' . trim((string) $this->manualFilters['user_name']) . '%');
            });
        }

        if (filled($this->manualFilters['editor_name'] ?? null)) {
            $query->whereHas('editor', function (Builder $editorQuery): void {
                $editorQuery->where('name', 'like', '%' . trim((string) $this->manualFilters['editor_name']) . '%');
            });
        }
    }

    private function normalizeReservationCodeFilter(mixed $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if ($digits === null || $digits === '') {
            return '';
        }

        return ltrim($digits, '0') ?: '0';
    }
}
