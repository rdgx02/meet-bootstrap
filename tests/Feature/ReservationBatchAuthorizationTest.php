<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationBatchAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function otherUsersReservation(): Reservation
    {
        $secretary = User::factory()->create(['role' => UserRole::Secretary]);
        $owner = User::factory()->create(['role' => UserRole::User]);
        $room = Room::create(['name' => '305', 'is_active' => true]);

        return Reservation::create([
            'room_id' => $room->id,
            'user_id' => $secretary->id,
            'owner_user_id' => $owner->id,
            'date' => '2026-04-12',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'title' => 'Reserva Alheia',
            'requester' => 'Outro',
        ]);
    }

    public function test_regular_user_cannot_export_another_users_reservation(): void
    {
        $reservation = $this->otherUsersReservation();
        $intruder = User::factory()->create(['role' => UserRole::User]);

        $response = $this->actingAs($intruder)->get(route('reservations.export-selected', [
            'ids' => (string) $reservation->id,
        ]));

        $response->assertForbidden();
    }

    public function test_regular_user_cannot_destroy_another_users_reservation_in_batch(): void
    {
        $reservation = $this->otherUsersReservation();
        $intruder = User::factory()->create(['role' => UserRole::User]);

        $response = $this->actingAs($intruder)->delete(route('reservations.destroy-selected'), [
            'ids' => (string) $reservation->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id]);
    }
}
