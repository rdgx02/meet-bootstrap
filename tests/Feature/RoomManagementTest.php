<?php

namespace Tests\Feature;

use App\Enums\UserRole;
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

    public function test_admin_can_delete_room(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $room = Room::create([
            'name' => 'Sala Excluir',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->delete(route('rooms.destroy', $room));

        $response->assertRedirect(route('rooms.index'));
        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    public function test_secretary_cannot_manage_rooms(): void
    {
        $secretary = User::factory()->create(['role' => UserRole::Secretary]);
        $room = Room::create([
            'name' => 'Sala Restrita',
            'is_active' => true,
        ]);

        $this->actingAs($secretary)
            ->get(route('rooms.index'))
            ->assertForbidden();

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
            ->delete(route('rooms.destroy', $room))
            ->assertForbidden();
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
