<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    /**
     * Somente admins podem gerenciar salas.
     */
    private function isAdmin(User $user): bool
    {
        return $user->isAdmin();
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, Room $room): bool
    {
        return $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Room $room): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Room $room): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(User $user, Room $room): bool
    {
        return $this->isAdmin($user);
    }

    public function forceDelete(User $user, Room $room): bool
    {
        return $this->isAdmin($user);
    }
}