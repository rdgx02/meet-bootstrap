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
}
