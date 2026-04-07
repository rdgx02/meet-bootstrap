<?php

namespace App\Actions\Reservations;

use App\Exceptions\RecurringReservationConflictException;
use App\Models\Reservation;
use App\Models\ReservationSeries;
use App\Services\RecurringReservationOccurrenceGenerator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UpdateReservationSeriesAction
{
    public function __construct(
        private readonly RecurringReservationOccurrenceGenerator $occurrenceGenerator
    ) {}

    public function execute(ReservationSeries $series, array $data): array
    {
        $editableReservations = $series->reservations()
            ->get()
            ->filter(fn (Reservation $reservation): bool => $this->reservationStartsInFuture($reservation));

        $generatedOccurrences = $this->occurrenceGenerator->generate($data)
            ->filter(fn (array $occurrence): bool => $this->occurrenceStartsInFuture($occurrence));

        if ($generatedOccurrences->isEmpty()) {
            throw new RecurringReservationConflictException(
                [],
                'A configuração informada não gera ocorrências futuras editáveis para esta série.'
            );
        }

        return DB::transaction(function () use ($series, $data, $editableReservations, $generatedOccurrences): array {
            $editableIds = $editableReservations->pluck('id');
            $conflicts = [];

            foreach ($generatedOccurrences as $occurrence) {
                $conflict = Reservation::query()
                    ->where('room_id', $occurrence['room_id'])
                    ->where('date', $occurrence['date'])
                    ->where(function ($query) use ($occurrence): void {
                        $query->where('start_time', '<', $occurrence['end_time'])
                            ->where('end_time', '>', $occurrence['start_time']);
                    })
                    ->when($editableIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $editableIds))
                    ->with('room')
                    ->orderBy('start_time')
                    ->lockForUpdate()
                    ->first();

                if ($conflict !== null) {
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

            if ($editableIds->isNotEmpty()) {
                Reservation::query()->whereIn('id', $editableIds)->delete();
            }

            $series->update([
                'room_id' => (int) $data['room_id'],
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
                'status' => 'active',
            ]);

            foreach ($generatedOccurrences as $occurrence) {
                Reservation::create([
                    ...$occurrence,
                    'series_id' => $series->id,
                    'user_id' => $series->user_id,
                    'original_date' => $occurrence['date'],
                    'is_exception' => false,
                ]);
            }

            return [
                'updated_count' => $generatedOccurrences->count(),
                'removed_count' => $editableIds->count(),
            ];
        });
    }

    private function reservationStartsInFuture(Reservation $reservation): bool
    {
        return Carbon::parse(sprintf('%s %s', $reservation->date, $reservation->start_time))->greaterThan(now());
    }

    private function occurrenceStartsInFuture(array $occurrence): bool
    {
        return Carbon::parse(sprintf('%s %s', $occurrence['date'], $occurrence['start_time']))->greaterThan(now());
    }
}
