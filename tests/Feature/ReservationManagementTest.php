<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\ReservationsTable;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ReservationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_can_create_reservation(): void
    {
        $user = User::factory()->create(['role' => UserRole::Secretary]);
        $room = Room::create(['name' => 'Lab 203', 'is_active' => true]);
        $date = now()->addDay()->toDateString();

        $response = $this->actingAs($user)->post(route('reservations.store'), [
            'room_id' => $room->id,
            'date' => $date,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'title' => 'Reuniao de planejamento',
            'requester' => 'Equipe Produto',
            'contact' => 'produto@example.com',
        ]);

        $response->assertRedirect(route('reservations.index'));
        $this->assertDatabaseHas('reservations', [
            'room_id' => $room->id,
            'user_id' => $user->id,
            'date' => $date,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);
    }

    public function test_regular_user_cannot_create_reservation(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);
        $room = Room::create(['name' => 'Lab 305', 'is_active' => true]);

        $response = $this->actingAs($user)->post(route('reservations.store'), [
            'room_id' => $room->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '13:00',
            'end_time' => '14:00',
            'title' => 'Tentativa sem permissao',
            'requester' => 'Usuario comum',
            'contact' => null,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_conflicting_reservation_returns_validation_error(): void
    {
        $user = User::factory()->create(['role' => UserRole::Secretary]);
        $room = Room::create(['name' => 'Sala 207', 'is_active' => true]);
        $date = now()->addDay()->toDateString();

        Reservation::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'date' => $date,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'title' => 'Reserva existente',
            'requester' => 'Secretaria',
            'contact' => null,
        ]);

        $response = $this->actingAs($user)
            ->from(route('reservations.create'))
            ->post(route('reservations.store'), [
                'room_id' => $room->id,
                'date' => $date,
                'start_time' => '10:30',
                'end_time' => '11:30',
                'title' => 'Reserva em conflito',
                'requester' => 'Secretaria',
                'contact' => null,
            ]);

        $response->assertRedirect(route('reservations.create'));
        $response->assertSessionHasErrors('start_time');
        $response->assertSessionHas('reservation_conflict', function (array $conflict) use ($room, $date): bool {
            return $conflict['room_name'] === $room->name
                && $conflict['date'] === Carbon::parse($date)->format('d/m/Y')
                && $conflict['start_time'] === '10:00'
                && $conflict['end_time'] === '11:00'
                && $conflict['title'] === 'Reserva existente'
                && $conflict['requester'] === 'Secretaria';
        });
        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_cannot_create_reservation_with_start_time_in_the_past_today(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 10, 54, 0, 'America/Sao_Paulo'));

        try {
            $user = User::factory()->create(['role' => UserRole::Secretary]);
            $room = Room::create(['name' => 'Sala 203', 'is_active' => true]);

            $response = $this->actingAs($user)
                ->from(route('reservations.create'))
                ->post(route('reservations.store'), [
                    'room_id' => $room->id,
                    'date' => now()->toDateString(),
                    'start_time' => '08:00',
                    'end_time' => '09:00',
                    'title' => 'Reserva passada no mesmo dia',
                    'requester' => 'Secretaria',
                    'contact' => null,
                ]);

            $response->assertRedirect(route('reservations.create'));
            $response->assertSessionHasErrors('start_time');
            $this->assertDatabaseCount('reservations', 0);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_past_reservation_cannot_be_edited_or_deleted_even_by_secretary(): void
    {
        $user = User::factory()->create(['role' => UserRole::Secretary]);
        $room = Room::create(['name' => 'Sala 305', 'is_active' => true]);

        $reservation = Reservation::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'date' => now()->subDay()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'title' => 'Reserva encerrada',
            'requester' => 'Secretaria',
            'contact' => null,
        ]);

        $this->actingAs($user)
            ->get(route('reservations.edit', $reservation))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('reservations.update', $reservation), [
                'room_id' => $room->id,
                'date' => now()->addDay()->toDateString(),
                'start_time' => '10:00',
                'end_time' => '11:00',
                'title' => 'Tentativa de alterar',
                'requester' => 'Secretaria',
                'contact' => null,
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('reservations.destroy', $reservation))
            ->assertForbidden();

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'title' => 'Reserva encerrada',
        ]);
    }

    public function test_secretary_can_delete_future_reservation_via_destroy_route(): void
    {
        $user = User::factory()->create(['role' => UserRole::Secretary]);
        $room = Room::create(['name' => 'Sala Rota Delete', 'is_active' => true]);

        $reservation = Reservation::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'title' => 'Reserva pela rota',
            'requester' => 'Secretaria',
            'contact' => null,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('reservations.destroy', $reservation));

        $response->assertRedirect(route('reservations.index'));
        $this->assertDatabaseMissing('reservations', [
            'id' => $reservation->id,
        ]);
    }

    public function test_secretary_can_delete_selected_future_reservations(): void
    {
        $user = User::factory()->create(['role' => UserRole::Secretary]);
        $room = Room::create(['name' => 'Sala Lote', 'is_active' => true]);

        $firstReservation = Reservation::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'title' => 'Reserva Lote 1',
            'requester' => 'Secretaria',
            'contact' => null,
        ]);

        $secondReservation = Reservation::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'date' => now()->addDays(2)->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'title' => 'Reserva Lote 2',
            'requester' => 'Secretaria',
            'contact' => null,
        ]);

        $response = $this->actingAs($user)->delete(route('reservations.destroy-selected'), [
            'ids' => implode(',', [$firstReservation->id, $secondReservation->id]),
        ]);

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHas('success', '2 agendamentos excluídos com sucesso!');
        $this->assertDatabaseMissing('reservations', ['id' => $firstReservation->id]);
        $this->assertDatabaseMissing('reservations', ['id' => $secondReservation->id]);
    }

    public function test_destroy_selected_requires_at_least_one_valid_selection(): void
    {
        $user = User::factory()->create(['role' => UserRole::Secretary]);

        $response = $this->actingAs($user)->delete(route('reservations.destroy-selected'), [
            'ids' => '',
        ]);

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHas('warning', 'Selecione ao menos um agendamento para excluir.');
    }

    public function test_secretary_can_export_selected_reservations_to_csv(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Secretary,
            'name' => 'Ana Secretaria',
        ]);
        $editor = User::factory()->create([
            'role' => UserRole::Admin,
            'name' => 'Carlos Editor',
        ]);
        $room = Room::create(['name' => 'Sala Exportacao', 'is_active' => true]);

        $reservation = Reservation::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'date' => '2026-03-26',
            'start_time' => '14:00',
            'end_time' => '15:30',
            'title' => 'Exportar Agenda',
            'requester' => 'Equipe Operacional',
            'contact' => null,
        ]);
        $reservation->updated_by = $editor->id;
        $reservation->save();

        $response = $this->actingAs($user)->get(route('reservations.export-selected', [
            'ids' => (string) $reservation->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader('content-disposition');

        $content = $response->streamedContent();

        $this->assertStringContainsString('Codigo;Sala;Titulo;Solicitante;Data;Inicio;Fim;"Criado por";"Editado por"', $content);
        $this->assertStringContainsString('AG-0000' . $reservation->id, $content);
        $this->assertStringContainsString('"Sala Exportacao";"Exportar Agenda";"Equipe Operacional";26/03/2026;14:00;15:30;"Ana Secretaria";"Carlos Editor"', $content);
    }

    public function test_index_shows_only_upcoming_reservations_including_today_active_ones(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 10, 54, 0, 'America/Sao_Paulo'));

        try {
            $user = User::factory()->create(['role' => UserRole::User]);
            $room = Room::create(['name' => 'Sala Agenda', 'is_active' => true]);

            Reservation::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'date' => now()->toDateString(),
                'start_time' => '08:00',
                'end_time' => '09:00',
                'title' => 'Encerrada Hoje',
                'requester' => 'Equipe',
                'contact' => null,
            ]);

            Reservation::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'date' => now()->toDateString(),
                'start_time' => '10:30',
                'end_time' => '11:30',
                'title' => 'Em Andamento Hoje',
                'requester' => 'Equipe',
                'contact' => null,
            ]);

            Reservation::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'date' => now()->addDay()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'title' => 'Reserva Futura',
                'requester' => 'Equipe',
                'contact' => null,
            ]);

            $response = $this->actingAs($user)->get(route('reservations.index'));

            $response->assertOk();
            $response->assertSeeText('Em Andamento Hoje');
            $response->assertSeeText('Reserva Futura');
            $response->assertDontSeeText('Encerrada Hoje');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_history_shows_only_past_reservations_including_ended_today(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 10, 54, 0, 'America/Sao_Paulo'));

        try {
            $user = User::factory()->create(['role' => UserRole::User]);
            $room = Room::create(['name' => 'Sala Historico', 'is_active' => true]);

            Reservation::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'date' => now()->subDay()->toDateString(),
                'start_time' => '09:00',
                'end_time' => '10:00',
                'title' => 'Passada no Historico',
                'requester' => 'Equipe',
                'contact' => null,
            ]);

            Reservation::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'date' => now()->toDateString(),
                'start_time' => '08:00',
                'end_time' => '09:00',
                'title' => 'Encerrada Hoje no Historico',
                'requester' => 'Equipe',
                'contact' => null,
            ]);

            Reservation::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'date' => now()->toDateString(),
                'start_time' => '10:30',
                'end_time' => '11:30',
                'title' => 'Em Andamento na Agenda',
                'requester' => 'Equipe',
                'contact' => null,
            ]);

            $response = $this->actingAs($user)->get(route('reservations.history'));

            $response->assertOk();
            $response->assertSeeText('Passada no Historico');
            $response->assertSeeText('Encerrada Hoje no Historico');
            $response->assertDontSeeText('Em Andamento na Agenda');
        } finally {
            Carbon::setTestNow();
        }
    }
}
