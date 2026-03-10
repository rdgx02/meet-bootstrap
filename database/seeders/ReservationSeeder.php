<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        // ===== CONFIG =====
        $totalToCreate = 10;

        // Cria a partir de amanhã (pra não sujar "hoje")
        $date = Carbon::tomorrow()->toDateString();

        // Horário de funcionamento
        $openHour = 8;   // 08:00
        $closeHour = 18;  // 18:00 (fim)
        $stepMin = 30;  // grade de 30 em 30 min

        $creatorUserId = User::query()
            ->whereIn('role', [UserRole::Secretary->value, UserRole::Admin->value])
            ->orderByRaw('CASE WHEN role = ? THEN 0 ELSE 1 END', [UserRole::Secretary->value])
            ->value('id');

        $rooms = Room::where('is_active', true)->orderBy('id')->get();

        if ($rooms->isEmpty()) {
            $this->command?->warn('Nenhuma sala ativa encontrada. Cadastre salas antes de rodar o seeder.');

            return;
        }

        if ($creatorUserId === null) {
            $this->command?->warn('Nenhum usuario com role secretary/admin encontrado. Reservas serao criadas sem autor.');
        }

        $titles = [
            'Reunião', 'Treinamento', 'Alinhamento', 'Planejamento', 'Apresentação',
            'Daily', 'Entrevista', 'Workshop', 'Mentoria', 'Revisão',
        ];

        $requesters = [
            'Fabio', 'Léo', 'Luiz', 'Guy', 'Renato', 'Carlos', 'Ana', 'Bruno', 'Camila', 'Diego',
        ];

        $created = 0;
        $skipped = 0;
        $attempts = 0;

        // tenta até criar exatamente 10 (limite de segurança pra não loopar pra sempre)
        while ($created < $totalToCreate && $attempts < 500) {
            $attempts++;

            $room = $rooms[$created % $rooms->count()];

            // duração aleatória: 30, 60, 90 ou 120 min
            $durations = [30, 60, 90, 120];
            $durationMin = $durations[array_rand($durations)];

            // último start possível (pra não passar do fechamento)
            $latestStart = ($closeHour * 60) - $durationMin;

            // escolhe um start em múltiplos de 30 minutos dentro do expediente
            $startMin = rand($openHour * 60, $latestStart);
            $startMin = intdiv($startMin, $stepMin) * $stepMin;

            $start = Carbon::createFromTime(0, 0)->addMinutes($startMin)->format('H:i');
            $end = Carbon::createFromTime(0, 0)->addMinutes($startMin + $durationMin)->format('H:i');

            // conflito (sobreposição) na mesma sala/data
            $hasConflict = Reservation::where('room_id', $room->id)
                ->where('date', $date)
                ->where(function ($q) use ($start, $end) {
                    $q->where('start_time', '<', $end)
                        ->where('end_time', '>', $start);
                })
                ->exists();

            if ($hasConflict) {
                $skipped++;

                continue;
            }

            Reservation::create([
                'room_id' => $room->id,
                'user_id' => $creatorUserId,
                'date' => $date,
                'start_time' => $start,
                'end_time' => $end,
                'title' => $titles[array_rand($titles)].' - '.$room->name,
                'requester' => $requesters[array_rand($requesters)],
                'contact' => null,
            ]);

            $created++;
        }

        if ($created < $totalToCreate) {
            $this->command?->warn("Seeder concluiu, mas não conseguiu criar {$totalToCreate}. Criados: {$created}. Conflitos ignorados: {$skipped}.");

            return;
        }

        $this->command?->info("Seeder concluído. Criados: {$created}. Tentativas com conflito ignoradas: {$skipped}.");
    }
}
