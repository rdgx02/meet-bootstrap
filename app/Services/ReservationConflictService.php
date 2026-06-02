<?php

namespace App\Services;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ReservationConflictService
{
    /**
     * Retorna true se existir conflito de horário para a sala/data informadas.
     * $ignore serve para UPDATE: pode ser um id ou um conjunto de ids a ignorar
     * (ex.: ocorrências de série que serão substituídas).
     */
    public function hasConflict(array $data, int|array|null $ignore = null, bool $lockForUpdate = false): bool
    {
        return $this->findConflict($data, $ignore, $lockForUpdate) !== null;
    }

    public function findConflict(array $data, int|array|null $ignore = null, bool $lockForUpdate = false): ?Reservation
    {
        $query = $this->buildConflictQuery($data, $ignore)
            ->with('room')
            ->orderBy('start_time');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    /**
     * Formata um conflito de ocorrência (de série recorrente) para exibição,
     * em PT-BR (d/m/Y e H:i). Fonte única usada pelas Actions de recorrência.
     *
     * @return array<string, string|null>
     */
    public function describeOccurrenceConflict(array $occurrence, Reservation $conflict): array
    {
        $conflict->loadMissing('room');

        return [
            'attempted_date' => Carbon::parse($occurrence['date'])->format('d/m/Y'),
            'attempted_start_time' => Carbon::parse($occurrence['start_time'])->format('H:i'),
            'attempted_end_time' => Carbon::parse($occurrence['end_time'])->format('H:i'),
            'room_name' => $conflict->room?->name ?? '-',
            'existing_title' => $conflict->title,
            'existing_requester' => $conflict->requester,
            'existing_start_time' => Carbon::parse($conflict->start_time)->format('H:i'),
            'existing_end_time' => Carbon::parse($conflict->end_time)->format('H:i'),
        ];
    }

    private function buildConflictQuery(array $data, int|array|null $ignore = null): Builder
    {
        $query = Reservation::query()
            ->where('room_id', $data['room_id'])
            ->where('date', $data['date'])
            ->where(function (Builder $query) use ($data): void {
                // sobreposicao: novo_inicio < fim_existente AND novo_fim > inicio_existente
                $query->where('start_time', '<', $data['end_time'])
                    ->where('end_time', '>', $data['start_time']);
            });

        $ignoreIds = collect(is_array($ignore) ? $ignore : [$ignore])
            ->filter(fn ($id): bool => $id !== null)
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($ignoreIds !== []) {
            $query->whereNotIn('id', $ignoreIds);
        }

        return $query;
    }
}
