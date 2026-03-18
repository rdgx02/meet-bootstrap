<?php

namespace App\Actions\Reservations;

use App\Models\Reservation;
use App\Models\ReservationSeries;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CancelReservationSeriesAction
{
    public function execute(ReservationSeries $series): array
    {
        return DB::transaction(function () use ($series): array {
            $futureReservations = $series->reservations()
                ->get()
                ->filter(function (Reservation $reservation): bool {
                    $startAt = Carbon::parse(sprintf('%s %s', $reservation->date, $reservation->start_time));

                    return $startAt->greaterThan(now());
                });

            $deletedCount = $futureReservations->count();

            if ($deletedCount > 0) {
                Reservation::query()
                    ->whereIn('id', $futureReservations->pluck('id'))
                    ->delete();
            }

            $series->update([
                'status' => 'cancelled',
            ]);

            return [
                'deleted_count' => $deletedCount,
            ];
        });
    }
}
