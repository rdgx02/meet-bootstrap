<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;

class ReservationPolicy
{
    private function canManageAgenda(User $user): bool
    {
        return $user->canManageAgenda();
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->canManageAgenda($user);
    }

    public function update(User $user, Reservation $reservation): bool
    {
        return $this->canManageAgenda($user)
            && ! $this->hasReservationEnded($reservation);
    }

    public function delete(User $user, Reservation $reservation): bool
    {
        return $this->canManageAgenda($user)
            && ! $this->hasReservationEnded($reservation);
    }

    private function hasReservationEnded(Reservation $reservation): bool
    {
        $reservationEnd = Carbon::parse(sprintf(
            '%s %s',
            $reservation->date,
            $reservation->end_time
        ));

        return $reservationEnd->lessThanOrEqualTo(now());
    }
}
