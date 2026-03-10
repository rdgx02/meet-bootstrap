<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = ['203', '207', '219', '305'];

        foreach ($rooms as $name) {
            Room::updateOrCreate(
                ['name' => $name], // condição de busca
                ['is_active' => true] // valores garantidos
            );
        }
    }
}
