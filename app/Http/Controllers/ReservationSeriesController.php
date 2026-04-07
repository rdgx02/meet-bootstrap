<?php

namespace App\Http\Controllers;

use App\Actions\Reservations\CancelReservationSeriesAction;
use App\Actions\Reservations\UpdateReservationSeriesAction;
use App\Exceptions\RecurringReservationConflictException;
use App\Http\Requests\UpdateReservationSeriesRequest;
use App\Models\ReservationSeries;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReservationSeriesController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', ReservationSeries::class);

        $seriesCollection = ReservationSeries::query()
            ->with(['room', 'user', 'reservations'])
            ->orderByRaw("CASE status WHEN 'active' THEN 0 ELSE 1 END")
            ->orderByDesc('starts_on')
            ->get();

        return view('reservation-series.index', [
            'seriesCollection' => $seriesCollection,
            'now' => now(),
        ]);
    }

    public function show(ReservationSeries $reservationSeries): View
    {
        $this->authorize('view', $reservationSeries);

        $reservationSeries->load([
            'room',
            'user',
            'reservations' => fn ($query) => $query->orderBy('date')->orderBy('start_time'),
        ]);

        return view('reservation-series.show', [
            'series' => $reservationSeries,
            'now' => now(),
        ]);
    }

    public function edit(ReservationSeries $reservationSeries): View
    {
        $this->authorize('update', $reservationSeries);

        $reservationSeries->load('room');

        return view('reservation-series.edit', [
            'series' => $reservationSeries,
            'rooms' => \App\Models\Room::active()->orderBy('name')->get(),
            'returnTo' => $this->seriesReturnTo($reservationSeries),
        ]);
    }

    public function update(
        UpdateReservationSeriesRequest $request,
        ReservationSeries $reservationSeries,
        UpdateReservationSeriesAction $updateReservationSeries
    ): RedirectResponse {
        if ($reservationSeries->status === 'cancelled') {
            return redirect()
                ->route('reservation-series.show', $reservationSeries)
                ->with('warning', 'Séries canceladas não podem ser editadas.');
        }

        try {
            $result = $updateReservationSeries->execute($reservationSeries, $request->validated());
        } catch (RecurringReservationConflictException $exception) {
            return back()
                ->withInput()
                ->with('recurring_conflicts', $exception->conflicts())
                ->withErrors([
                    'recurrence_ends_on' => $exception->getMessage(),
                ]);
        }

        return redirect()
            ->to($this->seriesRedirectTarget($request, $reservationSeries))
            ->with(
                'success',
                sprintf(
                    'Série atualizada com sucesso. %d ocorrências futuras foram recriadas.',
                    $result['updated_count']
                )
            );
    }

    public function cancel(
        ReservationSeries $reservationSeries,
        CancelReservationSeriesAction $cancelReservationSeries
    ): RedirectResponse {
        $this->authorize('cancel', $reservationSeries);

        if ($reservationSeries->status === 'cancelled') {
            return redirect()
                ->route('reservation-series.show', $reservationSeries)
                ->with('warning', 'Essa série já estava cancelada.');
        }

        $result = $cancelReservationSeries->execute($reservationSeries);

        return redirect()
            ->route('reservation-series.show', $reservationSeries)
            ->with(
                'success',
                $result['deleted_count'] === 1
                    ? 'Série cancelada. 1 ocorrência futura foi removida.'
                    : sprintf('Série cancelada. %d ocorrências futuras foram removidas.', $result['deleted_count'])
            );
    }

    private function seriesReturnTo(ReservationSeries $reservationSeries): string
    {
        return request()->query('from') === 'index'
            ? route('reservation-series.index')
            : route('reservation-series.show', $reservationSeries);
    }

    private function seriesRedirectTarget(\Illuminate\Http\Request $request, ReservationSeries $reservationSeries): string
    {
        return $request->input('from') === 'index'
            ? route('reservation-series.index')
            : route('reservation-series.show', $reservationSeries);
    }
}
