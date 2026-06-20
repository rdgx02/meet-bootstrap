<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function makeReservation(User $creator, User $owner, Room $room): Reservation
    {
        return Reservation::create([
            'room_id' => $room->id,
            'user_id' => $creator->id,
            'owner_user_id' => $owner->id,
            'date' => '2026-04-12',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'title' => 'Defesa Secreta',
            'requester' => 'Titular Real',
        ]);
    }

    public function test_regular_user_cannot_see_details_of_others_reservations(): void
    {
        $secretary = User::factory()->create(['role' => UserRole::Secretary]);
        $owner = User::factory()->create(['role' => UserRole::User, 'name' => 'Titular Real']);
        $other = User::factory()->create(['role' => UserRole::User]);
        $room = Room::create(['name' => '305', 'is_active' => true]);

        $this->makeReservation($secretary, $owner, $room);

        $response = $this->actingAs($other)->get(route('availability.index', ['date' => '2026-04-12']));

        $response->assertOk();
        // A ocupação (horário) continua visível...
        $response->assertSeeText('10:00 às 11:00');
        $response->assertSeeText('Reservado');
        // ...mas o título e o solicitante alheios, não.
        $response->assertDontSeeText('Defesa Secreta');
        $response->assertDontSeeText('Titular Real');
    }

    public function test_owner_sees_details_of_own_reservation(): void
    {
        $secretary = User::factory()->create(['role' => UserRole::Secretary]);
        $owner = User::factory()->create(['role' => UserRole::User, 'name' => 'Titular Real']);
        $room = Room::create(['name' => '305', 'is_active' => true]);

        $this->makeReservation($secretary, $owner, $room);

        $response = $this->actingAs($owner)->get(route('availability.index', ['date' => '2026-04-12']));

        $response->assertOk();
        $response->assertSeeText('Defesa Secreta');
    }

    public function test_manager_sees_details_of_all_reservations(): void
    {
        $secretary = User::factory()->create(['role' => UserRole::Secretary]);
        $owner = User::factory()->create(['role' => UserRole::User, 'name' => 'Titular Real']);
        $room = Room::create(['name' => '305', 'is_active' => true]);

        $this->makeReservation($secretary, $owner, $room);

        $response = $this->actingAs($secretary)->get(route('availability.index', ['date' => '2026-04-12']));

        $response->assertOk();
        $response->assertSeeText('Defesa Secreta');
    }
}
