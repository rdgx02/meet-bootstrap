<?php

namespace App\Policies;

use App\Models\ReservationSeries;
use App\Models\User;

class ReservationSeriesPolicy
{
    private function canManageAgenda(User $user): bool
    {
        return $user->canManageAgenda();
    }

    public function viewAny(User $user): bool
    {
        return $this->canManageAgenda($user);
    }

    public function view(User $user, ReservationSeries $series): bool
    {
        return $this->canManageAgenda($user);
    }

    public function cancel(User $user, ReservationSeries $series): bool
    {
        return $this->canManageAgenda($user);
    }
}
