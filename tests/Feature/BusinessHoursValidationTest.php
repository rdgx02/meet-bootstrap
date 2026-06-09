<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessHoursValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function singlePayload(Room $room, User $owner, array $overrides = []): array
    {
        return array_merge([
            'room_id' => $room->id,
            'owner_user_id' => $owner->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'title' => 'Reuniao',
            'requester' => 'Equipe',
            'phone' => '+55 21 99999-9999',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function recurringPayload(Room $room, User $owner, array $overrides = []): array
    {
        return array_merge([
            'booking_mode' => 'recurring',
            'room_id' => $room->id,
            'owner_user_id' => $owner->id,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'title' => 'Serie',
            'requester' => 'Equipe',
            'phone' => '+55 21 99999-9999',
            'recurrence_starts_on' => now()->addDay()->toDateString(),
            'recurrence_ends_on' => now()->addWeek()->toDateString(),
            'recurrence_frequency' => 'weekly',
            'recurrence_weekdays' => [1],
        ], $overrides);
    }

    public function test_single_reservation_rejected_when_start_before_opening(): void
    {
        $user = User::factory()->create(['role' => UserRole::Secretary]);
        $owner = User::factory()->create(['role' => UserRole::User]);
        $room = Room::create(['name' => 'Sala Janela', 'is_active' => true]);

        $response = $this->actingAs($user)
            ->from(route('reservations.create'))
            ->post(route('reservations.store'), $this->singlePayload($room, $owner, [
                'start_time' => '07:00',
                'end_time' => '09:00',
            ]));

        $response->assertSessionHasErrors('start_time');
        $this->assertDatabaseMissing('reservations', ['title' => 'Reuniao']);
    }

    public function test_single_reservation_rejected_when_end_after_closing(): void
    {
        $user = User::factory()->create(['role' => UserRole::Secretary]);
        $owner = User::factory()->create(['role' => UserRole::User]);
        $room = Room::create(['name' => 'Sala Janela', 'is_active' => true]);

        $response = $this->actingAs($user)
            ->from(route('reservations.create'))
            ->post(route('reservations.store'), $this->singlePayload($room, $owner, [
                'start_time' => '17:00',
                'end_time' => '19:00',
            ]));

        $response->assertSessionHasErrors('end_time');
        $this->assertDatabaseMissing('reservations', ['title' => 'Reuniao']);
    }

    public function test_start_at_closing_time_is_rejected(): void
    {
        $user = User::factory()->create(['role' => UserRole::Secretary]);
        $owner = User::factory()->create(['role' => UserRole::User]);
        $room = Room::create(['name' => 'Sala Janela', 'is_active' => true]);

        // Início exatamente às 18:00 não pode (a sala fecha às 18:00).
        $response = $this->actingAs($user)
            ->from(route('reservations.create'))
            ->post(route('reservations.store'), $this->singlePayload($room, $owner, [
                'start_time' => '18:00',
                'end_time' => '18:30',
            ]));

        $response->assertSessionHasErrors('start_time');
        $this->assertDatabaseMissing('reservations', ['title' => 'Reuniao']);
    }

    public function test_reservation_at_window_boundaries_is_accepted(): void
    {
        $user = User::factory()->create(['role' => UserRole::Secretary]);
        $owner = User::factory()->create(['role' => UserRole::User]);
        $room = Room::create(['name' => 'Sala Janela', 'is_active' => true]);

        // Limites válidos: começa às 08:00, termina exatamente às 18:00.
        $response = $this->actingAs($user)
            ->post(route('reservations.store'), $this->singlePayload($room, $owner, [
                'start_time' => '08:00',
                'end_time' => '18:00',
            ]));

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reservations', [
            'room_id' => $room->id,
            'start_time' => '08:00',
            'end_time' => '18:00',
        ]);
    }

    public function test_recurring_series_rejected_when_outside_window(): void
    {
        $user = User::factory()->create(['role' => UserRole::Secretary]);
        $owner = User::factory()->create(['role' => UserRole::User]);
        $room = Room::create(['name' => 'Sala Janela', 'is_active' => true]);

        $response = $this->actingAs($user)
            ->from(route('reservations.create'))
            ->post(route('reservations.store'), $this->recurringPayload($room, $owner, [
                'start_time' => '07:00',
                'end_time' => '08:00',
            ]));

        $response->assertSessionHasErrors('start_time');
        $this->assertDatabaseCount('reservation_series', 0);
    }

    public function test_recurring_series_within_window_is_accepted(): void
    {
        $user = User::factory()->create(['role' => UserRole::Secretary]);
        $owner = User::factory()->create(['role' => UserRole::User]);
        $room = Room::create(['name' => 'Sala Janela', 'is_active' => true]);

        $response = $this->actingAs($user)
            ->post(route('reservations.store'), $this->recurringPayload($room, $owner, [
                'start_time' => '09:00',
                'end_time' => '10:00',
            ]));

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reservation_series', [
            'room_id' => $room->id,
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);
    }
}
