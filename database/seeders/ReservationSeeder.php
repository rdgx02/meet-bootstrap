<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Reservation;
use App\Models\ReservationSeries;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::query()
            ->whereIn('role', [UserRole::Secretary->value, UserRole::Admin->value])
            ->orderByRaw('CASE WHEN role = ? THEN 0 ELSE 1 END', [UserRole::Secretary->value])
            ->first();

        $rooms = Room::where('is_active', true)->orderBy('id')->get();

        if ($rooms->isEmpty() || $creator === null) {
            $this->command?->warn('Seeder de reservas ignorado: faltam salas ativas ou usuario secretary/admin.');

            return;
        }

        // Titulares possíveis (professores + a própria secretaria). A secretaria
        // agenda em nome deles (owner_user_id).
        $owners = User::query()
            ->whereIn('role', [UserRole::User->value, UserRole::Secretary->value])
            ->get();

        $titles = [
            'Reunião de equipe', 'Treinamento interno', 'Alinhamento de projeto',
            'Banca de avaliação', 'Apresentação de resultados', 'Orientação de TCC',
            'Reunião com fornecedor', 'Workshop de capacitação', 'Defesa de mestrado', 'Mentoria',
        ];

        $durations = [30, 60, 90, 120];
        $created = 0;

        // ===== Reservas avulsas: de 10 dias atrás a 25 à frente (apenas dias úteis) =====
        $start = Carbon::today()->subDays(10);
        $end = Carbon::today()->addDays(25);

        for ($day = $start->copy(); $day->lessThanOrEqualTo($end); $day->addDay()) {
            if ($day->isWeekend()) {
                continue;
            }

            $perDay = rand(2, 4);

            for ($i = 0; $i < $perDay; $i++) {
                $room = $rooms->random();
                $owner = $owners->random();
                $duration = $durations[array_rand($durations)];

                $latestStart = (18 * 60) - $duration;          // não passa do fechamento
                $startMin = intdiv(rand(8 * 60, $latestStart), 30) * 30; // grade de 30 min

                $startTime = Carbon::createFromTime(0, 0)->addMinutes($startMin)->format('H:i');
                $endTime = Carbon::createFromTime(0, 0)->addMinutes($startMin + $duration)->format('H:i');

                $hasConflict = Reservation::where('room_id', $room->id)
                    ->where('date', $day->toDateString())
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime)
                    ->exists();

                if ($hasConflict) {
                    continue;
                }

                Reservation::create([
                    'room_id' => $room->id,
                    'user_id' => $creator->id,
                    'owner_user_id' => $owner->id,
                    'date' => $day->toDateString(),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'title' => $titles[array_rand($titles)],
                    'requester' => $owner->name,
                ]);

                $created++;
            }
        }

        // ===== Série semanal real: próximas 6 segundas, 14:00–15:00, sala 305 =====
        $seriesRoom = $rooms->firstWhere('name', '305') ?? $rooms->first();
        $seriesOwner = $owners->first(fn (User $u): bool => $u->role === UserRole::User) ?? $creator;

        $firstMonday = Carbon::today()->next(Carbon::MONDAY);
        $lastMonday = $firstMonday->copy()->addWeeks(5);

        $series = ReservationSeries::create([
            'room_id' => $seriesRoom->id,
            'user_id' => $creator->id,
            'owner_user_id' => $seriesOwner->id,
            'starts_on' => $firstMonday->toDateString(),
            'ends_on' => $lastMonday->toDateString(),
            'start_time' => '14:00',
            'end_time' => '15:00',
            'title' => 'Reunião semanal do LADETEC',
            'requester' => $seriesOwner->name,
            'frequency' => 'weekly',
            'interval' => 1,
            'weekdays' => [Carbon::MONDAY], // ISO: segunda = 1
            'status' => 'active',
        ]);

        $seriesCount = 0;

        for ($m = $firstMonday->copy(); $m->lessThanOrEqualTo($lastMonday); $m->addWeek()) {
            $hasConflict = Reservation::where('room_id', $seriesRoom->id)
                ->where('date', $m->toDateString())
                ->where('start_time', '<', '15:00')
                ->where('end_time', '>', '14:00')
                ->exists();

            if ($hasConflict) {
                continue;
            }

            Reservation::create([
                'room_id' => $seriesRoom->id,
                'series_id' => $series->id,
                'user_id' => $creator->id,
                'owner_user_id' => $seriesOwner->id,
                'date' => $m->toDateString(),
                'start_time' => '14:00',
                'end_time' => '15:00',
                'title' => 'Reunião semanal do LADETEC',
                'requester' => $seriesOwner->name,
            ]);

            $seriesCount++;
        }

        $this->command?->info("Seeder de reservas: {$created} avulsas + {$seriesCount} ocorrências de série criadas.");
    }
}
