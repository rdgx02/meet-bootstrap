<?php

namespace App\Actions\Reservations;

use App\Exceptions\ReservationConflictException;
use App\Models\Reservation;
use App\Services\ReservationConflictService;
use Illuminate\Support\Facades\DB;

class UpdateReservationAction
{
    public function __construct(
        private readonly ReservationConflictService $conflictService
    ) {}

    public function execute(Reservation $reservation, array $data): Reservation
    {
        return DB::transaction(function () use ($reservation, $data): Reservation {
            $conflict = $this->conflictService->findConflict($data, $reservation->id, true);

            if ($conflict !== null) {
                throw ReservationConflictException::forRoomAndTime($conflict);
            }

            if ($reservation->series_id !== null) {
                $data['is_exception'] = true;
                $data['original_date'] = $reservation->original_date ?? $reservation->date;
            }

            $reservation->update($data);

            return $reservation->refresh();
        });
    }
}
