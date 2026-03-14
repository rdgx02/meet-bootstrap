<?php

namespace App\Http\Controllers;

use App\Actions\Reservations\CreateReservationAction;
use App\Actions\Reservations\CreateRecurringReservationSeriesAction;
use App\Actions\Reservations\UpdateReservationAction;
use App\Exceptions\RecurringReservationConflictException;
use App\Exceptions\ReservationConflictException;
use App\Http\Requests\ListReservationsRequest;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReservationController extends Controller
{
    public function index(ListReservationsRequest $request)
    {
        return $this->renderList($request, 'upcoming');
    }

    public function history(ListReservationsRequest $request): View
    {
        return $this->renderList($request, 'history');
    }

    public function create()
    {
        $this->authorize('create', Reservation::class);

        $rooms = Room::active()
            ->orderBy('name')
            ->get();

        return view('reservations.create', compact('rooms'));
    }

    public function store(
        StoreReservationRequest $request,
        CreateReservationAction $createReservation,
        CreateRecurringReservationSeriesAction $createRecurringReservationSeries
    ) {
        if ($request->validated('booking_mode') === 'recurring') {
            try {
                $result = $createRecurringReservationSeries->execute(
                    $request->validated(),
                    (int) $request->user()->id
                );
            } catch (RecurringReservationConflictException $exception) {
                return back()
                    ->withInput()
                    ->with('recurring_conflicts', $exception->conflicts())
                    ->withErrors([
                        'recurrence_ends_on' => $exception->getMessage(),
                    ]);
            }

            $redirect = redirect()->route('reservations.index')
                ->with(
                    'success',
                    $result['created_count'] === 1
                        ? 'Serie recorrente criada com 1 ocorrencia.'
                        : sprintf('Serie recorrente criada com %d ocorrencias.', $result['created_count'])
                );

            if ($result['conflicts'] !== []) {
                $redirect->with(
                    'warning',
                    sprintf(
                        '%d ocorrencias nao foram criadas por conflito de horario.',
                        count($result['conflicts'])
                    )
                );
            }

            return $redirect;
        }

        try {
            $createReservation->execute(
                $request->validated(),
                (int) $request->user()->id
            );
        } catch (ReservationConflictException $exception) {
            return back()
                ->withInput()
                ->with('reservation_conflict', $exception->context())
                ->withErrors([
                    'start_time' => $exception->getMessage(),
                ]);
        }

        return redirect()->route('reservations.index')
            ->with('success', 'Agendamento criado com sucesso!');
    }

    public function show(Reservation $reservation)
    {
        $this->authorize('view', $reservation);

        $reservation->load(['room', 'user', 'editor']);

        return view('reservations.show', compact('reservation'));
    }

    public function edit(Reservation $reservation)
    {
        $this->authorize('update', $reservation);

        $rooms = Room::active()
            ->orderBy('name')
            ->get();

        return view('reservations.edit', compact('reservation', 'rooms'));
    }

    public function update(
        UpdateReservationRequest $request,
        Reservation $reservation,
        UpdateReservationAction $updateReservation
    ) {
        try {
            $updateReservation->execute($reservation, $request->validated());
        } catch (ReservationConflictException $exception) {
            return back()
                ->withInput()
                ->with('reservation_conflict', $exception->context())
                ->withErrors([
                    'start_time' => $exception->getMessage(),
                ]);
        }

        return redirect()->route('reservations.index')
            ->with('success', 'Agendamento atualizado com sucesso!');
    }

    public function destroy(Reservation $reservation)
    {
        $this->authorize('delete', $reservation);

        $reservation->delete();

        return redirect()->route('reservations.index')
            ->with('success', 'Agendamento excluído com sucesso!');
    }

    public function destroySelected(Request $request)
    {
        $this->authorize('viewAny', Reservation::class);

        $selectedIds = collect(explode(',', (string) $request->input('ids')))
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->values();

        if ($selectedIds->isEmpty()) {
            return redirect()->route('reservations.index')
                ->with('warning', 'Selecione ao menos um agendamento para excluir.');
        }

        $reservations = Reservation::query()
            ->whereIn('id', $selectedIds)
            ->get();

        if ($reservations->isEmpty()) {
            return redirect()->route('reservations.index')
                ->with('warning', 'Nenhum agendamento selecionado foi encontrado.');
        }

        foreach ($reservations as $reservation) {
            $this->authorize('delete', $reservation);
        }

        DB::transaction(function () use ($reservations): void {
            foreach ($reservations as $reservation) {
                $reservation->delete();
            }
        });

        $count = $reservations->count();

        return redirect()->route('reservations.index')
            ->with(
                'success',
                $count === 1
                    ? 'Agendamento excluído com sucesso!'
                    : sprintf('%d agendamentos excluídos com sucesso!', $count)
            );
    }

    public function exportSelected(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Reservation::class);

        $selectedIds = collect(explode(',', (string) $request->query('ids')))
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->values();

        $reservations = Reservation::query()
            ->with(['room', 'user', 'editor'])
            ->whereIn('id', $selectedIds)
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

    private function renderList(
        ListReservationsRequest $request,
        string $scope
    ): View {
        $this->authorize('viewAny', Reservation::class);

        $title = $scope === 'history' ? 'Historico de Agendamentos' : 'Agendamentos';
        $subtitle = $scope === 'history'
            ? 'Consulte reservas que ja aconteceram (somente leitura).'
            : 'Consulte e gerencie os agendamentos de hoje e futuros.';
        $filters = $request->validated();

        return view('reservations.index', compact('scope', 'title', 'subtitle', 'filters'));
    }
}
