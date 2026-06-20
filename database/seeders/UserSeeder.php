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
        // Senha inicial obrigatoria via ambiente. Sem fallback: evita senha fraca
        // silenciosa em producao. Defina DEFAULT_USER_PASSWORD (forte) no .env.
        $defaultPassword = (string) env('DEFAULT_USER_PASSWORD', '');

        if (trim($defaultPassword) === '') {
            throw new \RuntimeException(
                'Defina DEFAULT_USER_PASSWORD (senha forte) no .env antes de rodar o seeder de usuarios.'
            );
        }

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

        // Professores/usuarios comuns — titulares de reservas (a secretaria agenda
        // em nome deles). Servem para demonstrar visibilidade e "reserva para".
        $professores = [
            'prof.henrique@meet.local' => 'Prof. Henrique',
            'prof.monica@meet.local' => 'Profa. Monica',
            'prof.silva@meet.local' => 'Prof. Silva',
        ];

        foreach ($professores as $email => $name) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make($defaultPassword),
                    'role' => UserRole::User,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->command?->warn('Usuarios iniciais atualizados. Altere as senhas no primeiro acesso.');
    }
}
