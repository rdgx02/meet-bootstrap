<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\SendWhatsAppMessageJob;
use App\Livewire\ReservationsTable;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReservationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_can_create_reservation(): void
    {
        $user = User::factory()->create(['role' => UserRole::Secretary]);
        $owner = User::factory()->create(['role' => UserRole::User]);
        $room = Room::create(['name' => 'Lab 203', 'is_active' => true]);
        $date = now()->addDay()->toDateString();

        $response = $this->actingAs($user)->post(route('reservations.store'), [
            'room_id' => $room->id,
            'owner_user_id' => $owner->id,
            'date' => $date,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'title' => 'Reuniao de planejamento',
            'requester' => 'Equipe Produto',
            'phone' => '+55 21 99999-9999',
        ]);

        $response->assertRedirect(route('reservations.index'));
        $this->assertDatabaseHas('reservations', [
            'room_id' => $room->id,
            'user_id' => $user->id,
            'owner_user_id' => $owner->id,
            'date' => $date,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);
    }

    public function test_regular_user_can_create_single_reservation(): void
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
            'phone' => '+55 21 99999-9999',
            'contact' => null,
        ]);

        $response->assertRedirect(route('reservations.index'));
        $this->assertDatabaseHas('reservations', [
            'room_id' => $room->id,
            'user_id' => $user->id,
            'title' => 'Tentativa sem permissao',
            'owner_user_id' => $user->id,
        ]);
    }

    public function test_secretary_created_reservation_is_visible_to_owner_user(): void
    {
        $secretary = User::factory()->create(['role' => UserRole::Secretary]);
        $owner = User::factory()->create(['role' => UserRole::User]);
        $room = Room::create(['name' => 'Lab Compartilhado', 'is_active' => true]);

        $reservation = Reservation::create([
            'room_id' => $room->id,
            'user_id' => $secretary->id,
            'owner_user_id' => $owner->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '15:00',
            'end_time' => '16:00',
            'title' => 'Reserva em nome do usuário',
            'requester' => $owner->name,
            'phone' => '+55 21 99999-9999',
        ]);

        $this->actingAs($owner)->get(route('reservations.index'))->assertOk();

        $titles = Reservation::query()
            ->visibleTo($owner)
            ->whereDate('date', '>', now()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->pluck('title')
            ->all();

        $this->assertContains('Reserva em nome do usuário', $titles);

        $this->actingAs($owner)
            ->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertSeeText($owner->name);
    }

    public function test_regular_user_cannot_create_recurring_reservation(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);
        $room = Room::create(['name' => 'Lab 305', 'is_active' => true]);

        $response = $this->actingAs($user)
            ->from(route('reservations.create'))
            ->post(route('reservations.store'), [
                'booking_mode' => 'recurring',
                'room_id' => $room->id,
                'start_time' => '13:00',
                'end_time' => '14:00',
                'title' => 'Serie proibida',
                'requester' => 'Usuario comum',
                'phone' => '+55 21 99999-9999',
                'recurrence_starts_on' => now()->addDay()->toDateString(),
                'recurrence_ends_on' => now()->addWeek()->toDateString(),
                'recurrence_frequency' => 'weekly',
                'recurrence_weekdays' => [1],
            ]);

        $response->assertRedirect(route('reservations.create'));
        $response->assertSessionHasErrors('booking_mode');
        $this->assertDatabaseMissing('reservation_series', [
            'title' => 'Serie proibida',
        ]);
    }

    public function test_single_reservation_creation_dispatches_whatsapp_notification(): void
    {
        Queue::fake();
        config([
            'services.evolution_whatsapp.enabled' => true,
            'services.evolution_whatsapp.queue' => true,
        ]);

        $user = User::factory()->create(['role' => UserRole::Secretary]);
        $owner = User::factory()->create(['role' => UserRole::User]);
        $room = Room::create(['name' => 'Lab 203', 'is_active' => true]);
        $date = now()->addDay()->toDateString();

        $this->actingAs($user)->post(route('reservations.store'), [
            'room_id' => $room->id,
            'owner_user_id' => $owner->id,
            'date' => $date,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'title' => 'Reuniao de planejamento',
            'requester' => 'Equipe Produto',
            'phone' => '+55 21 99999-9999',
        ])->assertRedirect(route('reservations.index'));

        Queue::assertPushed(SendWhatsAppMessageJob::class, function (SendWhatsAppMessageJob $job): bool {
            return $job->contextType === 'reservation_created'
                && $job->phone === '+55 21 99999-9999'
                && str_contains($job->message, 'CONFIRMAÇÃO DE AGENDAMENTO - SALAS')
                && str_contains($job->message, 'Status: Confirmado');
        });
    }

    public function test_single_reservation_update_dispatches_whatsapp_notification(): void
    {
        Queue::fake();
        config([
            'services.evolution_whatsapp.enabled' => true,
            'services.evolution_whatsapp.queue' => true,
        ]);

        $user = User::factory()->create(['role' => UserRole::Secretary]);
        $room = Room::create(['name' => 'Sala Atualizacao', 'is_active' => true]);

        $reservation = Reservation::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'owner_user_id' => $user->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'title' => 'Reserva Inicial',
            'requester' => 'Secretaria',
            'phone' => '+55 21 99999-9999',
        ]);

        $this->actingAs($user)->put(route('reservations.update', $reservation), [
            'room_id' => $room->id,
            'owner_user_id' => $user->id,
            'date' => $reservation->date,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'title' => 'Reserva Atualizada',
            'requester' => 'Secretaria',
            'phone' => '+55 21 99999-9999',
        ])->assertRedirect(route('reservations.index'));

        Queue::assertPushed(SendWhatsAppMessageJob::class, function (SendWhatsAppMessageJob $job): bool {
            return $job->contextType === 'reservation_updated'
                && str_contains($job->message, 'AGENDAMENTO ATUALIZADO - SALAS')
                && str_contains($job->message, 'Status: Atualizado');
        });
    }

    public function test_recurring_reservation_creation_dispatches_whatsapp_notification(): void
    {
        Queue::fake();
        config([
            'services.evolution_whatsapp.enabled' => true,
            'services.evolution_whatsapp.queue' => true,
        ]);

        $user = User::factory()->create(['role' => UserRole::Secretary]);
        $owner = User::factory()->create(['role' => UserRole::User]);
        $room = Room::create(['name' => 'Lab Serie', 'is_active' => true]);

        $this->actingAs($user)->post(route('reservations.store'), [
            'booking_mode' => 'recurring',
            'room_id' => $room->id,
            'owner_user_id' => $owner->id,
            'start_time' => '13:00',
            'end_time' => '14:00',
            'title' => 'Serie de teste',
            'requester' => 'Usuario comum',
            'phone' => '+55 21 99999-9999',
            'recurrence_starts_on' => now()->addDay()->toDateString(),
            'recurrence_ends_on' => now()->addWeek()->toDateString(),
            'recurrence_frequency' => 'weekly',
            'recurrence_weekdays' => [1],
        ])->assertRedirect(route('reservations.index'));

        Queue::assertPushed(SendWhatsAppMessageJob::class, function (SendWhatsAppMessageJob $job): bool {
            return $job->contextType === 'series_created'
                && str_contains($job->message, 'SÉRIE RECORRENTE CRIADA - SALAS')
                && str_contains($job->message, 'Status: Ativa');
        });
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
            'phone' => '+55 21 99999-9999',
            'contact' => null,
        ]);

        $response = $this->actingAs($user)
            ->from(route('reservations.create'))
            ->post(route('reservations.store'), [
                'room_id' => $room->id,
                'owner_user_id' => $user->id,
                'date' => $date,
                'start_time' => '10:30',
                'end_time' => '11:30',
                'title' => 'Reserva em conflito',
                'requester' => 'Secretaria',
                'phone' => '+55 21 99999-9999',
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
                    'owner_user_id' => $user->id,
                    'date' => now()->toDateString(),
                    'start_time' => '08:00',
                    'end_time' => '09:00',
                    'title' => 'Reserva passada no mesmo dia',
                    'requester' => 'Secretaria',
                    'phone' => '+55 21 99999-9999',
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
            'phone' => '+55 21 99999-9999',
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
                'phone' => '+55 21 99999-9999',
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
            'phone' => '+55 21 99999-9999',
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
            'phone' => '+55 21 99999-9999',
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
            'phone' => '+55 21 99999-9999',
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
            'phone' => '+55 21 99999-9999',
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

        $this->assertStringContainsString('Código;Sala;Título;Solicitante;Data;Início;Fim;"Criado por";"Editado por"', $content);
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
                'phone' => '+55 21 99999-9999',
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
                'phone' => '+55 21 99999-9999',
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
                'phone' => '+55 21 99999-9999',
            'contact' => null,
            ]);

            $response = $this->actingAs($user)->get(route('reservations.index'));

            $response->assertOk();
            $response->assertSeeText('Em Andamento Hoje');
            $response->assertSeeText('Reserva Futura');
            $response->assertDontSeeText('Encerrada Hoje');
            $response->assertSeeText('Aplicar filtros');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_create_action_is_exposed_in_sidebar_not_in_toolbar(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);

        $response = $this->actingAs($user)->get(route('reservations.index'));

        $response->assertOk();
        $response->assertSee(route('reservations.create'), false);
        $response->assertSeeText('Novo agendamento');
        $response->assertDontSeeText('Cadastrar Agendamento');
    }

    public function test_index_applies_manual_filters_from_query_string(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);
        $roomA = Room::create(['name' => 'Sala 203', 'is_active' => true]);
        $roomB = Room::create(['name' => 'Sala 305', 'is_active' => true]);

        Reservation::create([
            'room_id' => $roomA->id,
            'user_id' => $user->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'title' => 'LAGOA',
            'requester' => 'Equipe A',
            'phone' => '+55 21 99999-9999',
            'contact' => null,
        ]);

        Reservation::create([
            'room_id' => $roomB->id,
            'user_id' => $user->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'title' => 'TI',
            'requester' => 'Equipe B',
            'phone' => '+55 21 99999-9999',
            'contact' => null,
        ]);

        $response = $this->actingAs($user)->get(route('reservations.index', [
            'room_id' => $roomA->id,
            'title' => 'LAGOA',
        ]));

        $response->assertOk();
        $response->assertSeeText('LAGOA');
        $response->assertDontSeeText('Equipe B');
    }

    public function test_index_filters_by_formatted_code_from_query_string(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);
        $room = Room::create(['name' => 'Sala Código', 'is_active' => true]);

        $matchingReservation = Reservation::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'title' => 'Reserva do código formatado',
            'requester' => 'Equipe Código',
            'phone' => '+55 21 99999-9999',
            'contact' => null,
        ]);

        Reservation::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'date' => now()->addDays(2)->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'title' => 'Outra reserva',
            'requester' => 'Equipe B',
            'phone' => '+55 21 99999-9999',
            'contact' => null,
        ]);

        $response = $this->actingAs($user)->get(route('reservations.index', [
            'code' => 'AG-' . str_pad((string) $matchingReservation->id, 5, '0', STR_PAD_LEFT),
        ]));

        $response->assertOk();
        $response->assertSeeText('Reserva do código formatado');
        $response->assertDontSeeText('Outra reserva');
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
                'phone' => '+55 21 99999-9999',
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
                'phone' => '+55 21 99999-9999',
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
                'phone' => '+55 21 99999-9999',
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

    public function test_regular_user_sees_only_own_reservations_in_index_and_history(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 10, 54, 0, 'America/Sao_Paulo'));

        try {
            $user = User::factory()->create(['role' => UserRole::User]);
            $otherUser = User::factory()->create(['role' => UserRole::User]);
            $room = Room::create(['name' => 'Sala Privada', 'is_active' => true]);

            Reservation::create([
                'room_id' => $room->id,
                'user_id' => User::factory()->create(['role' => UserRole::Secretary])->id,
                'owner_user_id' => $user->id,
                'date' => '2026-03-11',
                'start_time' => '10:00',
                'end_time' => '11:00',
                'title' => 'Minha futura',
                'requester' => 'Meu nome',
                'phone' => '+55 21 99999-9999',
            ]);

            Reservation::create([
                'room_id' => $room->id,
                'user_id' => User::factory()->create(['role' => UserRole::Secretary])->id,
                'owner_user_id' => $otherUser->id,
                'date' => '2026-03-11',
                'start_time' => '12:00',
                'end_time' => '13:00',
                'title' => 'Futura de outra pessoa',
                'requester' => 'Outro nome',
                'phone' => '+55 21 98888-8888',
            ]);

            Reservation::create([
                'room_id' => $room->id,
                'user_id' => User::factory()->create(['role' => UserRole::Secretary])->id,
                'owner_user_id' => $user->id,
                'date' => '2026-03-09',
                'start_time' => '08:00',
                'end_time' => '09:00',
                'title' => 'Minha passada',
                'requester' => 'Meu nome',
                'phone' => '+55 21 99999-9999',
            ]);

            Reservation::create([
                'room_id' => $room->id,
                'user_id' => User::factory()->create(['role' => UserRole::Secretary])->id,
                'owner_user_id' => $otherUser->id,
                'date' => '2026-03-09',
                'start_time' => '10:00',
                'end_time' => '11:00',
                'title' => 'Passada de outra pessoa',
                'requester' => 'Outro nome',
                'phone' => '+55 21 98888-8888',
            ]);

            $this->actingAs($user)->get(route('reservations.index'))->assertOk();

            $upcomingTitles = Reservation::query()
                ->visibleTo($user)
                ->whereDate('date', '>', '2026-03-10')
                ->orderBy('date')
                ->orderBy('start_time')
                ->pluck('title')
                ->all();

            $this->assertSame(['Minha futura'], $upcomingTitles);

            $this->actingAs($user)->get(route('reservations.history'))->assertOk();

            $historyTitles = Reservation::query()
                ->visibleTo($user)
                ->whereDate('date', '<', '2026-03-10')
                ->orderBy('date')
                ->orderBy('start_time')
                ->pluck('title')
                ->all();

            $this->assertSame(['Minha passada'], $historyTitles);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_regular_user_cannot_view_other_users_reservation_details(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);
        $otherUser = User::factory()->create(['role' => UserRole::User]);
        $room = Room::create(['name' => 'Sala Restrita', 'is_active' => true]);

        $reservation = Reservation::create([
            'room_id' => $room->id,
            'user_id' => User::factory()->create(['role' => UserRole::Secretary])->id,
            'owner_user_id' => $otherUser->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'title' => 'Reserva de outra pessoa',
            'requester' => 'Outro nome',
            'phone' => '+55 21 98888-8888',
        ]);

        $this->actingAs($user)
            ->get(route('reservations.show', $reservation))
            ->assertForbidden();
    }

    public function test_reservations_table_exposes_expected_empty_state_message(): void
    {
        $component = new ReservationsTable();

        $this->assertSame(
            'Nenhum agendamento corresponde aos filtros informados.',
            $component->noDataLabel()
        );
    }

    public function test_reservations_table_datasource_filters_upcoming_scope(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 10, 54, 0, 'America/Sao_Paulo'));

        try {
            $user = User::factory()->create(['role' => UserRole::User]);
            $room = Room::create(['name' => 'Sala Datasource', 'is_active' => true]);

            Reservation::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'date' => '2026-03-09',
                'start_time' => '08:00',
                'end_time' => '09:00',
                'title' => 'Passada',
                'requester' => 'Equipe',
                'phone' => '+55 21 99999-9999',
            'contact' => null,
            ]);

            Reservation::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'date' => '2026-03-10',
                'start_time' => '08:00',
                'end_time' => '09:00',
                'title' => 'Encerrada Hoje',
                'requester' => 'Equipe',
                'phone' => '+55 21 99999-9999',
            'contact' => null,
            ]);

            Reservation::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'date' => '2026-03-10',
                'start_time' => '10:30',
                'end_time' => '11:30',
                'title' => 'Ativa Hoje',
                'requester' => 'Equipe',
                'phone' => '+55 21 99999-9999',
            'contact' => null,
            ]);

            Reservation::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'date' => '2026-03-11',
                'start_time' => '09:00',
                'end_time' => '10:00',
                'title' => 'Futura',
                'requester' => 'Equipe',
                'phone' => '+55 21 99999-9999',
            'contact' => null,
            ]);

            $component = new ReservationsTable();
            $component->scope = 'upcoming';

            $titles = $component->datasource()
                ->orderBy('date')
                ->orderBy('start_time')
                ->pluck('title')
                ->all();

            $this->assertSame(['Ativa Hoje', 'Futura'], $titles);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_reservations_table_datasource_filters_history_scope(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 10, 54, 0, 'America/Sao_Paulo'));

        try {
            $user = User::factory()->create(['role' => UserRole::User]);
            $room = Room::create(['name' => 'Sala Historico Datasource', 'is_active' => true]);

            Reservation::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'date' => '2026-03-09',
                'start_time' => '08:00',
                'end_time' => '09:00',
                'title' => 'Passada',
                'requester' => 'Equipe',
                'phone' => '+55 21 99999-9999',
            'contact' => null,
            ]);

            Reservation::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'date' => '2026-03-10',
                'start_time' => '08:00',
                'end_time' => '09:00',
                'title' => 'Encerrada Hoje',
                'requester' => 'Equipe',
                'phone' => '+55 21 99999-9999',
            'contact' => null,
            ]);

            Reservation::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'date' => '2026-03-10',
                'start_time' => '10:30',
                'end_time' => '11:30',
                'title' => 'Ativa Hoje',
                'requester' => 'Equipe',
                'phone' => '+55 21 99999-9999',
            'contact' => null,
            ]);

            $component = new ReservationsTable();
            $component->scope = 'history';

            $titles = $component->datasource()
                ->orderBy('date')
                ->orderBy('start_time')
                ->pluck('title')
                ->all();

            $this->assertSame(['Passada', 'Encerrada Hoje'], $titles);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_reservations_table_lists_active_rooms_and_inactive_rooms_with_visible_reservations(): void
    {
        Room::create(['name' => 'Zulu', 'is_active' => true]);
        $inactiveWithoutReservation = Room::create(['name' => 'Beta', 'is_active' => false]);
        $inactiveWithReservation = Room::create(['name' => '305', 'is_active' => false]);
        Room::create(['name' => 'Alfa', 'is_active' => true]);

        Reservation::create([
            'room_id' => $inactiveWithReservation->id,
            'user_id' => User::factory()->create(['role' => UserRole::Secretary])->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '18:00',
            'end_time' => '19:00',
            'title' => 'Reserva em sala inativa',
            'requester' => 'Equipe',
            'phone' => '+55 21 99999-9999',
            'contact' => null,
        ]);

        $component = new ReservationsTable();

        $rooms = $component->rooms()->pluck('name')->all();

        $this->assertSame(['305', 'Alfa', 'Zulu'], $rooms);
        $this->assertNotContains($inactiveWithoutReservation->name, $rooms);
    }
}

