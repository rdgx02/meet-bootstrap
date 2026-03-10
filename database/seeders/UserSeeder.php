<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = (string) env('DEFAULT_USER_PASSWORD', '12345678');

        User::updateOrCreate(
            ['email' => 'admin@meet.local'],
            [
                'name' => 'Administrador',
                'password' => Hash::make($defaultPassword),
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'secretaria@meet.local'],
            [
                'name' => 'Secretaria',
                'password' => Hash::make($defaultPassword),
                'role' => UserRole::Secretary,
                'email_verified_at' => now(),
            ]
        );

        $this->command?->warn('Usuarios iniciais atualizados. Altere as senhas no primeiro acesso.');
    }
}
