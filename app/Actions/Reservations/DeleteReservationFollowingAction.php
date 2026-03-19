<?php

namespace App\Actions\Reservations;

use App\Models\Reservation;
use App\Models\ReservationSeries;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DeleteReservationFollowingAction
{
    public function execute(Reservation $reservation): void
    {
        $series = $reservation->series;

        if (! $series instanceof ReservationSeries) {
            throw new \InvalidArgumentException('A reserva informada nao pertence a uma serie.');
        }

        DB::transaction(function () use ($reservation, $series): void {
            Reservation::query()
                ->where('series_id', $series->id)
                ->whereDate('date', '>=', $reservation->date)
                ->delete();

            $series->update([
                'ends_on' => Carbon::parse($reservation->date)->subDay()->toDateString(),
            ]);
        });
    }
}
