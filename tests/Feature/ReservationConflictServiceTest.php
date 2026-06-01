<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Services\ReservationConflictService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationConflictServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeReservation(Room $room, User $user, string $start, string $end): Reservation
    {
        return Reservation::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'owner_user_id' => $user->id,
            'date' => '2026-03-19',
            'start_time' => $start,
            'end_time' => $end,
            'title' => "Reserva {$start}",
            'requester' => 'Solicitante',
            'phone' => '+55 21 99999-9999',
        ]);
    }

    public function test_find_conflict_ignores_a_set_of_ids_but_still_detects_others(): void
    {
        $user = User::factory()->create();
        $room = Room::create(['name' => 'Sala Conflito', 'is_active' => true]);

        $a = $this->makeReservation($room, $user, '08:00', '09:00');
        $b = $this->makeReservation($room, $user, '09:00', '10:00');
        $c = $this->makeReservation($room, $user, '08:30', '09:30');

        $service = app(ReservationConflictService::class);

        // Janela 08:00-10:00 sobrepõe a, b e c. Ignorando a e b, ainda detecta c.
        $conflict = $service->findConflict([
            'room_id' => $room->id,
            'date' => '2026-03-19',
            'start_time' => '08:00',
            'end_time' => '10:00',
        ], [$a->id, $b->id]);

        $this->assertNotNull($conflict);
        $this->assertSame($c->id, $conflict->id);

        // Ignorando a, b e c, não há mais conflito.
        $this->assertNull($service->findConflict([
            'room_id' => $room->id,
            'date' => '2026-03-19',
            'start_time' => '08:00',
            'end_time' => '10:00',
        ], [$a->id, $b->id, $c->id]));
    }

    public function test_find_conflict_still_accepts_a_single_int_id(): void
    {
        $user = User::factory()->create();
        $room = Room::create(['name' => 'Sala Compat', 'is_active' => true]);

        $existing = $this->makeReservation($room, $user, '08:00', '09:00');

        $service = app(ReservationConflictService::class);
        $window = [
            'room_id' => $room->id,
            'date' => '2026-03-19',
            'start_time' => '08:00',
            'end_time' => '09:00',
        ];

        // Sem ignorar: detecta. Ignorando o próprio id (int): não detecta.
        $this->assertNotNull($service->findConflict($window));
        $this->assertNull($service->findConflict($window, $existing->id));
    }

    public function test_describe_occurrence_conflict_returns_formatted_keys(): void
    {
        $user = User::factory()->create();
        $room = Room::create(['name' => 'Sala Formatada', 'is_active' => true]);
        $conflict = $this->makeReservation($room, $user, '08:00', '09:00');

        $occurrence = [
            'date' => '2026-03-19',
            'start_time' => '08:30',
            'end_time' => '09:30',
        ];

        $described = app(ReservationConflictService::class)
            ->describeOccurrenceConflict($occurrence, $conflict);

        $this->assertSame([
            'attempted_date' => '19/03/2026',
            'attempted_start_time' => '08:30',
            'attempted_end_time' => '09:30',
            'room_name' => 'Sala Formatada',
            'existing_title' => 'Reserva 08:00',
            'existing_requester' => 'Solicitante',
            'existing_start_time' => '08:00',
            'existing_end_time' => '09:00',
        ], $described);
    }
}
