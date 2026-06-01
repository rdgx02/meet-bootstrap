<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_room(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->post(route('rooms.store'), [
            'name' => 'Sala 101',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('rooms.index'));

        $this->assertDatabaseHas('rooms', [
            'name' => 'Sala 101',
            'is_active' => 1,
        ]);
    }

    public function test_admin_can_update_room(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $room = Room::create([
            'name' => 'Sala Antiga',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('rooms.update', $room), [
            'name' => 'Sala Atualizada',
            'is_active' => '0',
        ]);

        $response->assertRedirect(route('rooms.index'));

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'name' => 'Sala Atualizada',
            'is_active' => 0,
        ]);
    }

    public function test_admin_can_archive_room_preserving_reservations(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $room = Room::create([
            'name' => 'Sala Arquivar',
            'is_active' => true,
        ]);

        // Reserva futura nesta sala deve sobreviver ao arquivamento.
        $reservation = Reservation::create([
            'room_id' => $room->id,
            'user_id' => $admin->id,
            'owner_user_id' => $admin->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'title' => 'Reunião preservada',
            'requester' => $admin->name,
            'phone' => '+55 21 99999-9999',
        ]);

        $response = $this->actingAs($admin)->patch(route('rooms.archive', $room));

        $response->assertRedirect(route('rooms.index'));

        // A sala continua existindo, apenas inativa.
        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'is_active' => 0,
        ]);

        // E a reserva NÃO foi apagada em cascata.
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'room_id' => $room->id,
        ]);
    }

    public function test_admin_can_restore_archived_room(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $room = Room::create([
            'name' => 'Sala Inativa',
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)->patch(route('rooms.restore', $room));

        $response->assertRedirect(route('rooms.index'));

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'is_active' => 1,
        ]);
    }

    public function test_archived_room_is_hidden_from_new_reservation_form(): void
    {
        $secretary = User::factory()->create(['role' => UserRole::Secretary]);
        $activeRoom = Room::create(['name' => 'Sala Disponivel', 'is_active' => true]);
        $archivedRoom = Room::create(['name' => 'Sala Arquivada', 'is_active' => false]);

        $this->actingAs($secretary)
            ->get(route('reservations.create'))
            ->assertOk()
            ->assertSeeText('Sala Disponivel')
            ->assertDontSeeText('Sala Arquivada');
    }

    public function test_secretary_can_view_rooms_but_cannot_manage_them(): void
    {
        $secretary = User::factory()->create(['role' => UserRole::Secretary]);
        $room = Room::create([
            'name' => 'Sala Restrita',
            'is_active' => true,
        ]);

        $this->actingAs($secretary)
            ->get(route('rooms.index'))
            ->assertOk()
            ->assertSeeText('Sala Restrita');

        $this->actingAs($secretary)
            ->get(route('rooms.create'))
            ->assertForbidden();

        $this->actingAs($secretary)
            ->post(route('rooms.store'), [
                'name' => 'Sala Nova',
                'is_active' => '1',
            ])
            ->assertForbidden();

        $this->actingAs($secretary)
            ->get(route('rooms.edit', $room))
            ->assertForbidden();

        $this->actingAs($secretary)
            ->put(route('rooms.update', $room), [
                'name' => 'Sala Bloqueada',
                'is_active' => '1',
            ])
            ->assertForbidden();

        $this->actingAs($secretary)
            ->patch(route('rooms.archive', $room))
            ->assertForbidden();

        $this->actingAs($secretary)
            ->patch(route('rooms.restore', $room))
            ->assertForbidden();
    }

    public function test_regular_user_can_view_rooms_and_see_menu_link(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);
        Room::create([
            'name' => 'Sala Consulta',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('rooms.index'))
            ->assertOk()
            ->assertSeeText('Sala Consulta');

        $this->actingAs($user)
            ->get(route('reservations.index'))
            ->assertOk()
            ->assertSee(route('rooms.index'), false)
            ->assertSeeText('Salas');
    }

    public function test_room_name_must_be_unique(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Room::create([
            'name' => 'Sala Unica',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('rooms.create'))
            ->post(route('rooms.store'), [
                'name' => 'Sala Unica',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('rooms.create'));
        $response->assertSessionHasErrors('name');
    }
}
