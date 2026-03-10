<?php

namespace App\Services;

use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;

class ReservationConflictService
{
    /**
     * Retorna true se existir conflito de horário para a sala/data informadas.
     * $ignoreId serve para UPDATE (ignorar o próprio agendamento).
     */
    public function hasConflict(array $data, ?int $ignoreId = null, bool $lockForUpdate = false): bool
    {
        return $this->findConflict($data, $ignoreId, $lockForUpdate) !== null;
    }

    public function findConflict(array $data, ?int $ignoreId = null, bool $lockForUpdate = false): ?Reservation
    {
        $query = $this->buildConflictQuery($data, $ignoreId)
            ->with('room')
            ->orderBy('start_time');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    private function buildConflictQuery(array $data, ?int $ignoreId = null): Builder
    {
        $query = Reservation::query()
            ->where('room_id', $data['room_id'])
            ->where('date', $data['date'])
            ->where(function (Builder $query) use ($data): void {
                // sobreposicao: novo_inicio < fim_existente AND novo_fim > inicio_existente
                $query->where('start_time', '<', $data['end_time'])
                    ->where('end_time', '>', $data['start_time']);
            });

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query;
    }
}
