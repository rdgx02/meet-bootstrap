<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,        // cria perfis base de acesso
            RoomSeeder::class,        // primeiro cria salas
            ReservationSeeder::class, // depois cria agendamentos
        ]);
    }
}
