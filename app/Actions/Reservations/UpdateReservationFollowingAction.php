<?php

namespace App\Actions\Reservations;

use App\Exceptions\RecurringReservationConflictException;
use App\Models\Reservation;
use App\Models\ReservationSeries;
use App\Services\RecurringReservationOccurrenceGenerator;
use App\Services\ReservationConflictService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateReservationFollowingAction
{
    public function __construct(
        private readonly RecurringReservationOccurrenceGenerator $occurrenceGenerator,
        private readonly ReservationConflictService $conflictService
    ) {}

    public function execute(Reservation $reservation, array $data): ReservationSeries
    {
        $series = $reservation->series;

        if (! $series instanceof ReservationSeries) {
            throw new \InvalidArgumentException('A reserva informada não pertence a uma série.');
        }

        $originalSeriesEndsOn = (string) $series->ends_on;
        $originalReservationDate = (string) $reservation->date;
        $generatedOccurrences = $this->occurrenceGenerator->generate([
            'room_id' => $data['room_id'],
            'title' => $data['title'],
            'requester' => $data['requester'],
            'phone' => $data['phone'],
            'contact' => $data['contact'] ?? null,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'recurrence_starts_on' => $data['date'],
            'recurrence_ends_on' => $originalSeriesEndsOn,
            'recurrence_frequency' => $series->frequency,
            'recurrence_weekdays' => $series->weekdays ?? [],
        ]);

        return DB::transaction(function () use ($series, $reservation, $data, $originalReservationDate, $originalSeriesEndsOn, $generatedOccurrences): ReservationSeries {
            $followingReservations = Reservation::query()
                ->where('series_id', $series->id)
                ->whereDate('date', '>=', $originalReservationDate)
                ->get();

            $followingIds = $followingReservations->pluck('id')->all();
            $conflicts = [];

            foreach ($generatedOccurrences as $occurrence) {
                $conflict = $this->conflictService->findConflict($occurrence, null, true);

                if ($conflict !== null && ! in_array($conflict->id, $followingIds, true)) {
                    $conflicts[] = [
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

            if ($conflicts !== []) {
                throw RecurringReservationConflictException::forOccurrences($conflicts);
            }

            Reservation::query()
                ->whereIn('id', $followingIds)
                ->delete();

            $series->update([
                'ends_on' => Carbon::parse($originalReservationDate)->subDay()->toDateString(),
            ]);

            $newSeries = ReservationSeries::create([
                'room_id' => (int) $data['room_id'],
                'user_id' => $series->user_id,
                'starts_on' => (string) $data['date'],
                'ends_on' => $originalSeriesEndsOn,
                'start_time' => (string) $data['start_time'],
                'end_time' => (string) $data['end_time'],
                'title' => (string) $data['title'],
                'requester' => (string) $data['requester'],
                'phone' => (string) $data['phone'],
                'contact' => $data['contact'] ?: null,
                'frequency' => (string) $series->frequency,
                'interval' => (int) $series->interval,
                'weekdays' => $series->weekdays,
                'conflict_mode' => (string) $series->conflict_mode,
                'status' => 'active',
            ]);

            foreach ($generatedOccurrences as $occurrence) {
                Reservation::create([
                    ...$occurrence,
                    'series_id' => $newSeries->id,
                    'user_id' => $series->user_id,
                    'original_date' => $occurrence['date'],
                    'is_exception' => false,
                ]);
            }

            return $newSeries;
        });
    }
}
