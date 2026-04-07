<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Reservation;
use App\Models\ReservationSeries;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationSeriesManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_can_view_reservation_series_index_and_show(): void
    {
        $secretary = User::factory()->create(['role' => UserRole::Secretary]);
        $room = Room::create(['name' => 'Sala Serie', 'is_active' => true]);
        $series = ReservationSeries::create([
            'room_id' => $room->id,
            'user_id' => $secretary->id,
            'starts_on' => now()->addDay()->toDateString(),
            'ends_on' => now()->addDays(10)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'title' => 'Serie Semanal',
            'requester' => 'Secretaria',
            'contact' => null,
            'frequency' => 'weekly',
            'interval' => 1,
            'weekdays' => [1, 3],
            'conflict_mode' => 'strict',
            'status' => 'active',
        ]);

        $this->actingAs($secretary)
            ->get(route('reservation-series.index'))
            ->assertOk()
            ->assertSeeText('Serie Semanal');

        $this->actingAs($secretary)
            ->get(route('reservation-series.show', $series))
            ->assertOk()
            ->assertSeeText('Serie Semanal');
    }

    public function test_regular_user_cannot_access_reservation_series_management(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);

        $this->actingAs($user)
            ->get(route('reservation-series.index'))
            ->assertForbidden();
    }

    public function test_cancel_series_marks_series_as_cancelled_and_removes_only_future_occurrences(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 18, 10, 0, 0, 'America/Sao_Paulo'));

        try {
            $secretary = User::factory()->create(['role' => UserRole::Secretary]);
            $room = Room::create(['name' => 'Sala Cancelamento', 'is_active' => true]);
            $series = ReservationSeries::create([
                'room_id' => $room->id,
                'user_id' => $secretary->id,
                'starts_on' => now()->subDay()->toDateString(),
                'ends_on' => now()->addDays(10)->toDateString(),
                'start_time' => '09:00',
                'end_time' => '10:00',
                'title' => 'Serie para cancelar',
                'requester' => 'Secretaria',
                'contact' => null,
                'frequency' => 'daily',
                'interval' => 1,
                'weekdays' => null,
                'conflict_mode' => 'strict',
                'status' => 'active',
            ]);

            $pastOccurrence = Reservation::create([
                'room_id' => $room->id,
                'series_id' => $series->id,
                'user_id' => $secretary->id,
                'date' => now()->subDay()->toDateString(),
                'original_date' => now()->subDay()->toDateString(),
                'is_exception' => false,
                'start_time' => '09:00',
                'end_time' => '10:00',
                'title' => 'Serie para cancelar',
                'requester' => 'Secretaria',
                'contact' => null,
            ]);

            $activeOccurrence = Reservation::create([
                'room_id' => $room->id,
                'series_id' => $series->id,
                'user_id' => $secretary->id,
                'date' => now()->toDateString(),
                'original_date' => now()->toDateString(),
                'is_exception' => false,
                'start_time' => '09:00',
                'end_time' => '11:00',
                'title' => 'Serie para cancelar',
                'requester' => 'Secretaria',
                'contact' => null,
            ]);

            $futureOccurrence = Reservation::create([
                'room_id' => $room->id,
                'series_id' => $series->id,
                'user_id' => $secretary->id,
                'date' => now()->addDay()->toDateString(),
                'original_date' => now()->addDay()->toDateString(),
                'is_exception' => false,
                'start_time' => '09:00',
                'end_time' => '10:00',
                'title' => 'Serie para cancelar',
                'requester' => 'Secretaria',
                'contact' => null,
            ]);

            $response = $this->actingAs($secretary)
                ->patch(route('reservation-series.cancel', $series));

            $response->assertRedirect(route('reservation-series.show', $series));

            $this->assertDatabaseHas('reservation_series', [
                'id' => $series->id,
                'status' => 'cancelled',
            ]);

            $this->assertDatabaseHas('reservations', ['id' => $pastOccurrence->id]);
            $this->assertDatabaseHas('reservations', ['id' => $activeOccurrence->id]);
            $this->assertDatabaseMissing('reservations', ['id' => $futureOccurrence->id]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_secretary_can_edit_series_and_recreate_future_occurrences(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 18, 10, 0, 0, 'America/Sao_Paulo'));

        try {
            $secretary = User::factory()->create(['role' => UserRole::Secretary]);
            $roomA = Room::create(['name' => 'Sala A', 'is_active' => true]);
            $roomB = Room::create(['name' => 'Sala B', 'is_active' => true]);
            $series = ReservationSeries::create([
                'room_id' => $roomA->id,
                'user_id' => $secretary->id,
                'starts_on' => now()->subDay()->toDateString(),
                'ends_on' => now()->addDays(7)->toDateString(),
                'start_time' => '09:00',
                'end_time' => '10:00',
                'title' => 'Serie Original',
                'requester' => 'Secretaria',
                'contact' => null,
                'frequency' => 'daily',
                'interval' => 1,
                'weekdays' => null,
                'conflict_mode' => 'strict',
                'status' => 'active',
            ]);

            $pastOccurrence = Reservation::create([
                'room_id' => $roomA->id,
                'series_id' => $series->id,
                'user_id' => $secretary->id,
                'date' => now()->subDay()->toDateString(),
                'original_date' => now()->subDay()->toDateString(),
                'is_exception' => false,
                'start_time' => '09:00',
                'end_time' => '10:00',
                'title' => 'Serie Original',
                'requester' => 'Secretaria',
                'contact' => null,
            ]);

            $futureOccurrence = Reservation::create([
                'room_id' => $roomA->id,
                'series_id' => $series->id,
                'user_id' => $secretary->id,
                'date' => now()->addDay()->toDateString(),
                'original_date' => now()->addDay()->toDateString(),
                'is_exception' => false,
                'start_time' => '09:00',
                'end_time' => '10:00',
                'title' => 'Serie Original',
                'requester' => 'Secretaria',
                'contact' => null,
            ]);

            $response = $this->actingAs($secretary)
                ->put(route('reservation-series.update', $series), [
                    'room_id' => $roomB->id,
                    'title' => 'Serie Atualizada',
                    'requester' => 'Equipe Operacional',
                    'contact' => 'contato@example.com',
                    'start_time' => '14:00',
                    'end_time' => '15:00',
                    'recurrence_starts_on' => now()->subDay()->toDateString(),
                    'recurrence_ends_on' => now()->addDays(3)->toDateString(),
                    'recurrence_frequency' => 'daily',
                ]);

            $response->assertRedirect(route('reservation-series.show', $series));

            $this->assertDatabaseHas('reservation_series', [
                'id' => $series->id,
                'room_id' => $roomB->id,
                'title' => 'Serie Atualizada',
                'requester' => 'Equipe Operacional',
                'contact' => 'contato@example.com',
                'start_time' => '14:00',
                'end_time' => '15:00',
            ]);

            $this->assertDatabaseHas('reservations', [
                'id' => $pastOccurrence->id,
                'room_id' => $roomA->id,
                'title' => 'Serie Original',
            ]);

            $this->assertDatabaseMissing('reservations', [
                'id' => $futureOccurrence->id,
            ]);

            $this->assertDatabaseHas('reservations', [
                'series_id' => $series->id,
                'room_id' => $roomB->id,
                'title' => 'Serie Atualizada',
                'requester' => 'Equipe Operacional',
                'date' => now()->addDay()->toDateString(),
                'start_time' => '14:00',
                'end_time' => '15:00',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_series_detail_shows_edit_occurrence_only_for_future_items(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 18, 10, 0, 0, 'America/Sao_Paulo'));

        try {
            $secretary = User::factory()->create(['role' => UserRole::Secretary]);
            $room = Room::create(['name' => 'Sala UX Serie', 'is_active' => true]);
            $series = ReservationSeries::create([
                'room_id' => $room->id,
                'user_id' => $secretary->id,
                'starts_on' => now()->subDay()->toDateString(),
                'ends_on' => now()->addDays(2)->toDateString(),
                'start_time' => '09:00',
                'end_time' => '10:00',
                'title' => 'Serie UX',
                'requester' => 'Secretaria',
                'contact' => null,
                'frequency' => 'daily',
                'interval' => 1,
                'weekdays' => null,
                'conflict_mode' => 'strict',
                'status' => 'active',
            ]);

            $pastOccurrence = Reservation::create([
                'room_id' => $room->id,
                'series_id' => $series->id,
                'user_id' => $secretary->id,
                'date' => now()->toDateString(),
                'original_date' => now()->toDateString(),
                'is_exception' => false,
                'start_time' => '08:00',
                'end_time' => '09:00',
                'title' => 'Serie UX',
                'requester' => 'Secretaria',
                'contact' => null,
            ]);

            $futureOccurrence = Reservation::create([
                'room_id' => $room->id,
                'series_id' => $series->id,
                'user_id' => $secretary->id,
                'date' => now()->addDay()->toDateString(),
                'original_date' => now()->addDay()->toDateString(),
                'is_exception' => false,
                'start_time' => '08:00',
                'end_time' => '09:00',
                'title' => 'Serie UX',
                'requester' => 'Secretaria',
                'contact' => null,
            ]);

            $response = $this->actingAs($secretary)
                ->get(route('reservation-series.show', $series));

            $response->assertOk();
            $response->assertSee(route('reservations.edit', $futureOccurrence), false);
            $response->assertDontSee(route('reservations.edit', $pastOccurrence), false);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_series_detail_buttons_point_to_series_routes(): void
    {
        $secretary = User::factory()->create(['role' => UserRole::Secretary]);
        $room = Room::create(['name' => 'Sala Botoes Serie', 'is_active' => true]);
        $series = ReservationSeries::create([
            'room_id' => $room->id,
            'user_id' => $secretary->id,
            'starts_on' => now()->addDay()->toDateString(),
            'ends_on' => now()->addDays(10)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'title' => 'Serie Botoes',
            'requester' => 'Secretaria',
            'contact' => null,
            'frequency' => 'weekly',
            'interval' => 1,
            'weekdays' => [1, 3],
            'conflict_mode' => 'strict',
            'status' => 'active',
        ]);

        $response = $this->actingAs($secretary)
            ->get(route('reservation-series.show', $series));

        $response->assertOk();
        $response->assertSee(route('reservation-series.index'), false);
        $response->assertSee(route('reservation-series.edit', $series), false);
        $response->assertSee(route('reservation-series.cancel', $series), false);
        $response->assertSeeText('Ocorrências futuras');
    }

    public function test_series_edit_buttons_point_to_series_routes(): void
    {
        $secretary = User::factory()->create(['role' => UserRole::Secretary]);
        $room = Room::create(['name' => 'Sala Edicao Serie', 'is_active' => true]);
        $series = ReservationSeries::create([
            'room_id' => $room->id,
            'user_id' => $secretary->id,
            'starts_on' => now()->addDay()->toDateString(),
            'ends_on' => now()->addDays(10)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'title' => 'Serie Edicao',
            'requester' => 'Secretaria',
            'contact' => null,
            'frequency' => 'weekly',
            'interval' => 1,
            'weekdays' => [1, 3],
            'conflict_mode' => 'strict',
            'status' => 'active',
        ]);

        $response = $this->actingAs($secretary)
            ->get(route('reservation-series.edit', $series));

        $response->assertOk();
        $response->assertSee(route('reservation-series.show', $series), false);
        $response->assertSee(route('reservation-series.update', $series), false);
    }

    public function test_series_edit_from_index_preserves_return_to_index(): void
    {
        $secretary = User::factory()->create(['role' => UserRole::Secretary]);
        $room = Room::create(['name' => 'Sala Retorno Index', 'is_active' => true]);
        $series = ReservationSeries::create([
            'room_id' => $room->id,
            'user_id' => $secretary->id,
            'starts_on' => now()->addDay()->toDateString(),
            'ends_on' => now()->addDays(10)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'title' => 'Serie Retorno',
            'requester' => 'Secretaria',
            'contact' => null,
            'frequency' => 'weekly',
            'interval' => 1,
            'weekdays' => [1, 3],
            'conflict_mode' => 'strict',
            'status' => 'active',
        ]);

        $response = $this->actingAs($secretary)
            ->get(route('reservation-series.edit', $series, false) . '?from=index');

        $response->assertOk();
        $response->assertSee(route('reservation-series.index'), false);
        $response->assertSee('name="from" value="index"', false);

        $updateResponse = $this->actingAs($secretary)
            ->put(route('reservation-series.update', $series), [
                'room_id' => $room->id,
                'title' => 'Serie Retorno Atualizada',
                'requester' => 'Secretaria',
                'contact' => null,
                'start_time' => '09:00',
                'end_time' => '10:00',
                'recurrence_starts_on' => $series->starts_on,
                'recurrence_ends_on' => $series->ends_on,
                'recurrence_frequency' => 'weekly',
                'recurrence_weekdays' => [1, 3],
                'from' => 'index',
            ]);

        $updateResponse->assertRedirect(route('reservation-series.index'));
    }

    public function test_occurrence_links_from_series_preserve_series_context(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 18, 10, 0, 0, 'America/Sao_Paulo'));

        try {
            $secretary = User::factory()->create(['role' => UserRole::Secretary]);
            $room = Room::create(['name' => 'Sala Contexto Serie', 'is_active' => true]);
            $series = ReservationSeries::create([
                'room_id' => $room->id,
                'user_id' => $secretary->id,
                'starts_on' => now()->subDay()->toDateString(),
                'ends_on' => now()->addDays(3)->toDateString(),
                'start_time' => '08:00',
                'end_time' => '09:00',
                'title' => 'Serie Contexto',
                'requester' => 'Secretaria',
                'contact' => null,
                'frequency' => 'daily',
                'interval' => 1,
                'weekdays' => null,
                'conflict_mode' => 'strict',
                'status' => 'active',
            ]);

            $futureOccurrence = Reservation::create([
                'room_id' => $room->id,
                'series_id' => $series->id,
                'user_id' => $secretary->id,
                'date' => now()->addDay()->toDateString(),
                'original_date' => now()->addDay()->toDateString(),
                'is_exception' => false,
                'start_time' => '08:00',
                'end_time' => '09:00',
                'title' => 'Serie Contexto',
                'requester' => 'Secretaria',
                'contact' => null,
            ]);

            $showResponse = $this->actingAs($secretary)
                ->get(route('reservations.show', $futureOccurrence, false) . '?from=series&series=' . $series->id);

            $showResponse->assertOk();
            $showResponse->assertSee(route('reservation-series.show', $series), false);
            $showResponse->assertSee(route('reservations.edit', $futureOccurrence, false) . '?from=series&series=' . $series->id, false);
            $showResponse->assertSeeText('Impacto desta e próximas');
            $showResponse->assertSeeText('Impacto de toda a série');

            $editResponse = $this->actingAs($secretary)
                ->get(route('reservations.edit', $futureOccurrence, false) . '?from=series&series=' . $series->id);

            $editResponse->assertOk();
            $editResponse->assertSee(route('reservation-series.show', $series), false);

            $updateResponse = $this->actingAs($secretary)
                ->put(route('reservations.update', $futureOccurrence), [
                    'room_id' => $room->id,
                    'date' => $futureOccurrence->date,
                    'start_time' => '10:00',
                    'end_time' => '11:00',
                    'title' => 'Ocorrencia Ajustada',
                    'requester' => 'Secretaria',
                    'contact' => null,
                    'series_scope' => 'occurrence',
                    'from' => 'series',
                    'series' => $series->id,
                ]);

            $updateResponse->assertRedirect(route('reservation-series.show', $series));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_occurrence_update_with_following_scope_splits_series(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 18, 10, 0, 0, 'America/Sao_Paulo'));

        try {
            $secretary = User::factory()->create(['role' => UserRole::Secretary]);
            $room = Room::create(['name' => 'Sala Split', 'is_active' => true]);
            $series = ReservationSeries::create([
                'room_id' => $room->id,
                'user_id' => $secretary->id,
                'starts_on' => '2026-03-17',
                'ends_on' => '2026-03-25',
                'start_time' => '08:00',
                'end_time' => '09:00',
                'title' => 'Serie Split',
                'requester' => 'Secretaria',
                'contact' => null,
                'frequency' => 'daily',
                'interval' => 1,
                'weekdays' => null,
                'conflict_mode' => 'strict',
                'status' => 'active',
            ]);

            Reservation::create([
                'room_id' => $room->id,
                'series_id' => $series->id,
                'user_id' => $secretary->id,
                'date' => '2026-03-17',
                'original_date' => '2026-03-17',
                'is_exception' => false,
                'start_time' => '08:00',
                'end_time' => '09:00',
                'title' => 'Serie Split',
                'requester' => 'Secretaria',
                'contact' => null,
            ]);

            $target = Reservation::create([
                'room_id' => $room->id,
                'series_id' => $series->id,
                'user_id' => $secretary->id,
                'date' => '2026-03-19',
                'original_date' => '2026-03-19',
                'is_exception' => false,
                'start_time' => '08:00',
                'end_time' => '09:00',
                'title' => 'Serie Split',
                'requester' => 'Secretaria',
                'contact' => null,
            ]);

            Reservation::create([
                'room_id' => $room->id,
                'series_id' => $series->id,
                'user_id' => $secretary->id,
                'date' => '2026-03-20',
                'original_date' => '2026-03-20',
                'is_exception' => false,
                'start_time' => '08:00',
                'end_time' => '09:00',
                'title' => 'Serie Split',
                'requester' => 'Secretaria',
                'contact' => null,
            ]);

            $response = $this->actingAs($secretary)->put(route('reservations.update', $target), [
                'room_id' => $room->id,
                'date' => '2026-03-19',
                'start_time' => '10:00',
                'end_time' => '11:00',
                'title' => 'Serie Split Nova',
                'requester' => 'Equipe',
                'contact' => 'time@example.com',
                'series_scope' => 'following',
                'from' => 'series',
                'series' => $series->id,
            ]);

            $newSeries = ReservationSeries::query()->where('title', 'Serie Split Nova')->latest('id')->first();

            $response->assertRedirect(route('reservation-series.show', $series));
            $this->assertNotNull($newSeries);
            $this->assertDatabaseHas('reservation_series', [
                'id' => $series->id,
                'ends_on' => '2026-03-18',
            ]);
            $this->assertDatabaseHas('reservation_series', [
                'id' => $newSeries->id,
                'starts_on' => '2026-03-19',
                'ends_on' => '2026-03-25',
                'start_time' => '10:00',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_occurrence_update_with_all_scope_updates_series_future(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 18, 10, 0, 0, 'America/Sao_Paulo'));

        try {
            $secretary = User::factory()->create(['role' => UserRole::Secretary]);
            $room = Room::create(['name' => 'Sala All', 'is_active' => true]);
            $series = ReservationSeries::create([
                'room_id' => $room->id,
                'user_id' => $secretary->id,
                'starts_on' => '2026-03-17',
                'ends_on' => '2026-03-25',
                'start_time' => '08:00',
                'end_time' => '09:00',
                'title' => 'Serie All',
                'requester' => 'Secretaria',
                'contact' => null,
                'frequency' => 'daily',
                'interval' => 1,
                'weekdays' => null,
                'conflict_mode' => 'strict',
                'status' => 'active',
            ]);

            $target = Reservation::create([
                'room_id' => $room->id,
                'series_id' => $series->id,
                'user_id' => $secretary->id,
                'date' => '2026-03-19',
                'original_date' => '2026-03-19',
                'is_exception' => false,
                'start_time' => '08:00',
                'end_time' => '09:00',
                'title' => 'Serie All',
                'requester' => 'Secretaria',
                'contact' => null,
            ]);

            $response = $this->actingAs($secretary)->put(route('reservations.update', $target), [
                'room_id' => $room->id,
                'date' => '2026-03-19',
                'start_time' => '12:00',
                'end_time' => '13:00',
                'title' => 'Serie All Atualizada',
                'requester' => 'Equipe Toda',
                'contact' => 'all@example.com',
                'series_scope' => 'all',
                'from' => 'series',
                'series' => $series->id,
            ]);

            $response->assertRedirect(route('reservation-series.show', $series));
            $this->assertDatabaseHas('reservation_series', [
                'id' => $series->id,
                'title' => 'Serie All Atualizada',
                'start_time' => '12:00',
                'end_time' => '13:00',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_occurrence_delete_with_following_scope_truncates_series(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 18, 10, 0, 0, 'America/Sao_Paulo'));

        try {
            $secretary = User::factory()->create(['role' => UserRole::Secretary]);
            $room = Room::create(['name' => 'Sala Delete Following', 'is_active' => true]);
            $series = ReservationSeries::create([
                'room_id' => $room->id,
                'user_id' => $secretary->id,
                'starts_on' => '2026-03-17',
                'ends_on' => '2026-03-25',
                'start_time' => '08:00',
                'end_time' => '09:00',
                'title' => 'Serie Delete Following',
                'requester' => 'Secretaria',
                'contact' => null,
                'frequency' => 'daily',
                'interval' => 1,
                'weekdays' => null,
                'conflict_mode' => 'strict',
                'status' => 'active',
            ]);

            $target = Reservation::create([
                'room_id' => $room->id,
                'series_id' => $series->id,
                'user_id' => $secretary->id,
                'date' => '2026-03-19',
                'original_date' => '2026-03-19',
                'is_exception' => false,
                'start_time' => '08:00',
                'end_time' => '09:00',
                'title' => 'Serie Delete Following',
                'requester' => 'Secretaria',
                'contact' => null,
            ]);

            $future = Reservation::create([
                'room_id' => $room->id,
                'series_id' => $series->id,
                'user_id' => $secretary->id,
                'date' => '2026-03-20',
                'original_date' => '2026-03-20',
                'is_exception' => false,
                'start_time' => '08:00',
                'end_time' => '09:00',
                'title' => 'Serie Delete Following',
                'requester' => 'Secretaria',
                'contact' => null,
            ]);

            $response = $this->actingAs($secretary)->delete(route('reservations.destroy', $target), [
                'series_scope' => 'following',
                'from' => 'series',
                'series' => $series->id,
            ]);

            $response->assertRedirect(route('reservation-series.show', $series));
            $this->assertDatabaseMissing('reservations', ['id' => $target->id]);
            $this->assertDatabaseMissing('reservations', ['id' => $future->id]);
            $this->assertDatabaseHas('reservation_series', [
                'id' => $series->id,
                'ends_on' => '2026-03-18',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_occurrence_delete_with_all_scope_cancels_series(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 18, 10, 0, 0, 'America/Sao_Paulo'));

        try {
            $secretary = User::factory()->create(['role' => UserRole::Secretary]);
            $room = Room::create(['name' => 'Sala Delete All', 'is_active' => true]);
            $series = ReservationSeries::create([
                'room_id' => $room->id,
                'user_id' => $secretary->id,
                'starts_on' => '2026-03-17',
                'ends_on' => '2026-03-25',
                'start_time' => '08:00',
                'end_time' => '09:00',
                'title' => 'Serie Delete All',
                'requester' => 'Secretaria',
                'contact' => null,
                'frequency' => 'daily',
                'interval' => 1,
                'weekdays' => null,
                'conflict_mode' => 'strict',
                'status' => 'active',
            ]);

            $target = Reservation::create([
                'room_id' => $room->id,
                'series_id' => $series->id,
                'user_id' => $secretary->id,
                'date' => '2026-03-19',
                'original_date' => '2026-03-19',
                'is_exception' => false,
                'start_time' => '08:00',
                'end_time' => '09:00',
                'title' => 'Serie Delete All',
                'requester' => 'Secretaria',
                'contact' => null,
            ]);

            $response = $this->actingAs($secretary)->delete(route('reservations.destroy', $target), [
                'series_scope' => 'all',
                'from' => 'series',
                'series' => $series->id,
            ]);

            $response->assertRedirect(route('reservation-series.show', $series));
            $this->assertDatabaseHas('reservation_series', [
                'id' => $series->id,
                'status' => 'cancelled',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }
}
