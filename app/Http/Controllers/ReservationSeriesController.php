<?php

namespace App\Http\Controllers;

use App\Actions\Reservations\CancelReservationSeriesAction;
use App\Models\ReservationSeries;
use Carbon\Carbon;
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

    public function cancel(
        ReservationSeries $reservationSeries,
        CancelReservationSeriesAction $cancelReservationSeries
    ): RedirectResponse {
        $this->authorize('cancel', $reservationSeries);

        if ($reservationSeries->status === 'cancelled') {
            return redirect()
                ->route('reservation-series.show', $reservationSeries)
                ->with('warning', 'Essa serie ja estava cancelada.');
        }

        $result = $cancelReservationSeries->execute($reservationSeries);

        return redirect()
            ->route('reservation-series.show', $reservationSeries)
            ->with(
                'success',
                $result['deleted_count'] === 1
                    ? 'Serie cancelada. 1 ocorrencia futura foi removida.'
                    : sprintf('Serie cancelada. %d ocorrencias futuras foram removidas.', $result['deleted_count'])
            );
    }
}
