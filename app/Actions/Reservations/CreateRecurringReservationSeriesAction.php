<?php

namespace App\Actions\Reservations;

use App\Exceptions\RecurringReservationConflictException;
use App\Models\Reservation;
use App\Models\ReservationSeries;
use App\Services\RecurringReservationOccurrenceGenerator;
use App\Services\ReservationConflictService;
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

        return DB::transaction(function () use ($data, $creatorId, $occurrences): array {
            $validOccurrences = [];
            $conflicts = [];

            foreach ($occurrences as $occurrence) {
                $conflict = $this->conflictService->findConflict($occurrence, lockForUpdate: true);

                if ($conflict !== null) {
                    $conflicts[] = $this->conflictService->describeOccurrenceConflict($occurrence, $conflict);

                    continue;
                }

                $validOccurrences[] = $occurrence;
            }

            if ($conflicts !== []) {
                throw RecurringReservationConflictException::forOccurrences($conflicts);
            }

            if ($validOccurrences === []) {
                throw RecurringReservationConflictException::forOccurrences($conflicts);
            }

            $series = ReservationSeries::create([
                'room_id' => (int) $data['room_id'],
                'user_id' => $creatorId,
                'owner_user_id' => (int) $data['owner_user_id'],
                'starts_on' => (string) $data['recurrence_starts_on'],
                'ends_on' => (string) $data['recurrence_ends_on'],
                'start_time' => (string) $data['start_time'],
                'end_time' => (string) $data['end_time'],
                'title' => (string) $data['title'],
                'requester' => (string) $data['requester'],
                'phone' => (string) $data['phone'],
                'frequency' => (string) $data['recurrence_frequency'],
                'interval' => 1,
                'weekdays' => $data['recurrence_frequency'] === 'weekly'
                    ? array_values(array_map('intval', $data['recurrence_weekdays'] ?? []))
                    : null,
                'status' => 'active',
            ]);

            foreach ($validOccurrences as $occurrence) {
                Reservation::create([
                    ...$occurrence,
                    'series_id' => $series->id,
                    'user_id' => $creatorId,
                    'owner_user_id' => (int) $data['owner_user_id'],
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
}
