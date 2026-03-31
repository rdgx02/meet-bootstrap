<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AvailabilityConsultationTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_can_access_availability_page_from_sidebar(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);
        Room::create(['name' => '203', 'is_active' => true]);

        $response = $this->actingAs($user)->get(route('availability.index'));

        $response->assertOk();
        $response->assertSeeText('Disponibilidade');
        $response->assertSeeText('Consultar dia');
    }

    public function test_availability_page_shows_day_reservations_and_free_ranges_per_room(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
            'name' => 'Usuario Consulta',
        ]);

        $room203 = Room::create(['name' => '203', 'is_active' => true]);
        $room305 = Room::create(['name' => '305', 'is_active' => true]);

        Reservation::create([
            'room_id' => $room203->id,
            'user_id' => $user->id,
            'date' => '2026-04-12',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'title' => 'Reuniao Geral',
            'requester' => 'Secretaria',
            'contact' => null,
        ]);

        Reservation::create([
            'room_id' => $room203->id,
            'user_id' => $user->id,
            'date' => '2026-04-12',
            'start_time' => '13:00',
            'end_time' => '15:00',
            'title' => 'Treinamento',
            'requester' => 'Equipe TI',
            'contact' => null,
        ]);

        Reservation::create([
            'room_id' => $room305->id,
            'user_id' => $user->id,
            'date' => '2026-04-13',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'title' => 'Outro Dia',
            'requester' => 'Equipe B',
            'contact' => null,
        ]);

        $response = $this->actingAs($user)->get(route('availability.index', [
            'date' => '2026-04-12',
        ]));

        $response->assertOk();
        $response->assertSeeText('12/04/2026');
        $response->assertSeeText('Reuniao Geral');
        $response->assertSeeText('Treinamento');
        $response->assertDontSeeText('Outro Dia');
        $response->assertSeeText('08:00 as 09:00');
        $response->assertSeeText('10:00 as 13:00');
        $response->assertSeeText('15:00 as 18:00');
        $response->assertSeeText('Livre durante todo o periodo consultivo.');
    }

    public function test_availability_defaults_to_today_when_date_is_not_informed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 12, 9, 0, 0, 'America/Sao_Paulo'));

        try {
            $user = User::factory()->create(['role' => UserRole::User]);
            $room = Room::create(['name' => '207', 'is_active' => true]);

            Reservation::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'date' => '2026-04-12',
                'start_time' => '10:00',
                'end_time' => '11:00',
                'title' => 'Reserva de Hoje',
                'requester' => 'Equipe Hoje',
                'contact' => null,
            ]);

            $response = $this->actingAs($user)->get(route('availability.index'));

            $response->assertOk();
            $response->assertSeeText('12/04/2026');
            $response->assertSeeText('Reserva de Hoje');
        } finally {
            Carbon::setTestNow();
        }
    }
}
