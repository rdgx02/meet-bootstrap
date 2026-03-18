<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Maria Secretaria',
            'email' => 'maria@example.com',
            'role' => UserRole::Secretary->value,
            'is_active' => '1',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'maria@example.com',
            'role' => UserRole::Secretary->value,
            'is_active' => 1,
        ]);
    }

    public function test_admin_can_update_user_role_and_status(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);
        $managedUser = User::factory()->create([
            'role' => UserRole::User,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('users.update', $managedUser), [
            'name' => 'Usuario Atualizado',
            'email' => $managedUser->email,
            'role' => UserRole::Secretary->value,
            'is_active' => '0',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $managedUser->id,
            'name' => 'Usuario Atualizado',
            'role' => UserRole::Secretary->value,
            'is_active' => 0,
        ]);
    }

    public function test_secretary_cannot_manage_users(): void
    {
        $secretary = User::factory()->create([
            'role' => UserRole::Secretary,
            'is_active' => true,
        ]);
        $managedUser = User::factory()->create();

        $this->actingAs($secretary)
            ->get(route('users.index'))
            ->assertForbidden();

        $this->actingAs($secretary)
            ->get(route('users.create'))
            ->assertForbidden();

        $this->actingAs($secretary)
            ->post(route('users.store'), [
                'name' => 'Bloqueado',
                'email' => 'bloqueado@example.com',
                'role' => UserRole::User->value,
                'is_active' => '1',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertForbidden();

        $this->actingAs($secretary)
            ->get(route('users.edit', $managedUser))
            ->assertForbidden();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => 'password123',
            'is_active' => false,
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_last_active_admin_cannot_be_deactivated_or_demoted(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('users.edit', $admin))
            ->put(route('users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => UserRole::User->value,
                'is_active' => '0',
                'password' => '',
                'password_confirmation' => '',
            ]);

        $response->assertRedirect(route('users.edit', $admin));
        $response->assertSessionHasErrors(['role', 'is_active']);
    }
}
