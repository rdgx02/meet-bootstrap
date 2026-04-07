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
        $response->assertSeeText('Consultar disponibilidade');
        $response->assertSeeText('Disponibilidade por sala');
        $response->assertSeeText('Todas');
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
        $response->assertSeeText('Livre durante todo o período consultivo.');
    }

    public function test_availability_can_focus_on_single_room_summary(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);
        $room203 = Room::create(['name' => '203', 'is_active' => true]);
        $room305 = Room::create(['name' => '305', 'is_active' => true]);

        Reservation::create([
            'room_id' => $room305->id,
            'user_id' => $user->id,
            'date' => '2026-04-12',
            'start_time' => '16:00',
            'end_time' => '17:00',
            'title' => 'TI',
            'requester' => 'Equipe TI',
            'contact' => null,
        ]);

        Reservation::create([
            'room_id' => $room203->id,
            'user_id' => $user->id,
            'date' => '2026-04-12',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'title' => 'Reuniao 203',
            'requester' => 'Equipe A',
            'contact' => null,
        ]);

        $response = $this->actingAs($user)->get(route('availability.index', [
            'date' => '2026-04-12',
            'room_id' => $room305->id,
        ]));

        $response->assertOk();
        $response->assertSeeText('Sala 305');
        $response->assertSeeText('Parcialmente ocupada');
        $response->assertSeeText('08:00 as 16:00');
        $response->assertSeeText('17:00 as 18:00');
        $response->assertSeeText('16:00 as 17:00 - TI');
        $response->assertDontSeeText('Reuniao 203');
    }

    public function test_availability_lists_rooms_ordered_by_status_when_all_rooms_are_selected(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);
        $roomFree = Room::create(['name' => '207', 'is_active' => true]);
        $roomPartial = Room::create(['name' => '203', 'is_active' => true]);
        $roomBusy = Room::create(['name' => '305', 'is_active' => true]);

        Reservation::create([
            'room_id' => $roomPartial->id,
            'user_id' => $user->id,
            'date' => '2026-04-12',
            'start_time' => '08:00',
            'end_time' => '09:00',
            'title' => 'Parcial',
            'requester' => 'Equipe Parcial',
            'contact' => null,
        ]);

        Reservation::create([
            'room_id' => $roomBusy->id,
            'user_id' => $user->id,
            'date' => '2026-04-12',
            'start_time' => '08:00',
            'end_time' => '18:00',
            'title' => 'Dia inteiro',
            'requester' => 'Equipe Busy',
            'contact' => null,
        ]);

        $response = $this->actingAs($user)->get(route('availability.index', [
            'date' => '2026-04-12',
        ]));

        $response->assertOk();
        $response->assertSeeTextInOrder([
            '207',
            'Livre',
            '203',
            'Parcialmente ocupada',
            '305',
            'Ocupada',
        ]);
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

    public function test_availability_page_keeps_day_agenda_as_operational_support(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);
        $room = Room::create(['name' => '305', 'is_active' => true]);

        Reservation::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'date' => '2026-04-12',
            'start_time' => '14:00',
            'end_time' => '15:30',
            'title' => 'Conselho',
            'requester' => 'Diretoria',
            'contact' => null,
        ]);

        Reservation::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'date' => '2026-04-13',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'title' => 'Outro dia',
            'requester' => 'Equipe',
            'contact' => null,
        ]);

        $response = $this->actingAs($user)->get(route('availability.index', [
            'date' => '2026-04-12',
        ]));

        $response->assertOk();
        $response->assertSeeText('Agendamentos do dia');
        $response->assertSeeText('Conselho');
        $response->assertSeeText('14:00 as 15:30');
        $response->assertDontSeeText('Painel visual por sala');
    }

    public function test_day_agenda_metadata_shows_selected_room_when_filtering_by_room(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);
        $room = Room::create(['name' => '219', 'is_active' => true]);

        Reservation::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'date' => '2026-04-12',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'title' => 'Reuniao 219',
            'requester' => 'Equipe 219',
            'contact' => null,
        ]);

        $response = $this->actingAs($user)->get(route('availability.index', [
            'date' => '2026-04-12',
            'room_id' => $room->id,
        ]));

        $response->assertOk();
        $response->assertSeeText('Sala 219');
        $response->assertSeeText('Reservas 1');
    }

    public function test_availability_marks_room_as_occupied_when_day_is_fully_booked(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);
        $room = Room::create(['name' => '305', 'is_active' => true]);

        Reservation::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'date' => '2026-04-12',
            'start_time' => '08:00',
            'end_time' => '18:00',
            'title' => 'Dia inteiro',
            'requester' => 'Equipe Busy',
            'contact' => null,
        ]);

        $response = $this->actingAs($user)->get(route('availability.index', [
            'date' => '2026-04-12',
            'room_id' => $room->id,
        ]));

        $response->assertOk();
        $response->assertSeeText('Ocupada');
        $response->assertSeeText('Não há faixa livre dentro da janela consultiva.');
        $response->assertSeeText('08:00 as 18:00 - Dia inteiro');
    }

    public function test_availability_handles_absence_of_active_rooms(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);
        Room::create(['name' => '203', 'is_active' => false]);

        $response = $this->actingAs($user)->get(route('availability.index', [
            'date' => '2026-04-12',
        ]));

        $response->assertOk();
        $response->assertSeeText('Disponibilidade por sala');
        $response->assertDontSeeText('203');
        $response->assertSeeText('Salas livres 0');
        $response->assertSeeText('Salas ocupadas 0');
    }
}
