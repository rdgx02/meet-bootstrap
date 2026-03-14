<?php

namespace App\Actions\Reservations;

use App\Exceptions\RecurringReservationConflictException;
use App\Models\Reservation;
use App\Models\ReservationSeries;
use App\Services\RecurringReservationOccurrenceGenerator;
use App\Services\ReservationConflictService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateRecurringReservationSeriesAction
{
    public function __construct(
        private readonly RecurringReservationOccurrenceGenerator $occurrenceGenerator,
        private readonly ReservationConflictService $conflictService
    ) {}

    public function execute(array $data, int $creatorId): array
    {
        $occurrences = $this->occurrenceGenerator->generate($data);
        $conflictMode = 'strict';

        return DB::transaction(function () use ($data, $creatorId, $occurrences, $conflictMode): array {
            $validOccurrences = [];
            $conflicts = [];

            foreach ($occurrences as $occurrence) {
                $conflict = $this->conflictService->findConflict($occurrence, lockForUpdate: true);

                if ($conflict !== null) {
                    $conflicts[] = $this->formatConflict($occurrence, $conflict);
                    continue;
                }

                $validOccurrences[] = $occurrence;
            }

            if ($conflicts !== [] && $conflictMode === 'strict') {
                throw RecurringReservationConflictException::forOccurrences($conflicts);
            }

            if ($validOccurrences === []) {
                throw RecurringReservationConflictException::forOccurrences($conflicts);
            }

            $series = ReservationSeries::create([
                'room_id' => (int) $data['room_id'],
                'user_id' => $creatorId,
                'starts_on' => (string) $data['recurrence_starts_on'],
                'ends_on' => (string) $data['recurrence_ends_on'],
                'start_time' => (string) $data['start_time'],
                'end_time' => (string) $data['end_time'],
                'title' => (string) $data['title'],
                'requester' => (string) $data['requester'],
                'contact' => $data['contact'] ?: null,
                'frequency' => (string) $data['recurrence_frequency'],
                'interval' => 1,
                'weekdays' => $data['recurrence_frequency'] === 'weekly'
                    ? array_values(array_map('intval', $data['recurrence_weekdays'] ?? []))
                    : null,
                'conflict_mode' => $conflictMode,
                'status' => 'active',
            ]);

            foreach ($validOccurrences as $occurrence) {
                Reservation::create([
                    ...$occurrence,
                    'series_id' => $series->id,
                    'user_id' => $creatorId,
                    'original_date' => $occurrence['date'],
                    'is_exception' => false,
                ]);
            }

            return [
                'series' => $series,
                'created_count' => count($validOccurrences),
                'total_count' => $occurrences->count(),
                'conflicts' => $conflicts,
            ];
        });
    }

    private function formatConflict(array $occurrence, Reservation $conflict): array
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
}
