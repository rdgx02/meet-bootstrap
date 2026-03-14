<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class RecurringReservationOccurrenceGenerator
{
    public function generate(array $data): Collection
    {
        $startsOn = CarbonImmutable::parse($data['recurrence_starts_on']);
        $endsOn = CarbonImmutable::parse($data['recurrence_ends_on']);
        $frequency = (string) $data['recurrence_frequency'];

        $dates = match ($frequency) {
            'daily' => $this->generateDailyDates($startsOn, $endsOn),
            'weekly' => $this->generateWeeklyDates($startsOn, $endsOn, $data['recurrence_weekdays'] ?? []),
            'monthly' => $this->generateMonthlyDates($startsOn, $endsOn),
            default => collect(),
        };

        return $dates->values()->map(function (CarbonImmutable $date) use ($data): array {
            return [
                'room_id' => (int) $data['room_id'],
                'date' => $date->toDateString(),
                'start_time' => (string) $data['start_time'],
                'end_time' => (string) $data['end_time'],
                'title' => (string) $data['title'],
                'requester' => (string) $data['requester'],
                'contact' => $data['contact'] ?: null,
            ];
        });
    }

    private function generateDailyDates(CarbonImmutable $startsOn, CarbonImmutable $endsOn): Collection
    {
        $dates = collect();
        $cursor = $startsOn;

        while ($cursor->lessThanOrEqualTo($endsOn)) {
            $dates->push($cursor);
            $cursor = $cursor->addDay();
        }

        return $dates;
    }

    private function generateWeeklyDates(CarbonImmutable $startsOn, CarbonImmutable $endsOn, array $weekdays): Collection
    {
        $selectedWeekdays = collect($weekdays)
            ->map(fn (mixed $weekday): int => (int) $weekday)
            ->filter(fn (int $weekday): bool => in_array($weekday, [1, 2, 3, 4, 5, 6, 7], true))
            ->unique()
            ->sort()
            ->values()
            ->all();

        $dates = collect();
        $cursor = $startsOn;

        while ($cursor->lessThanOrEqualTo($endsOn)) {
            if (in_array($cursor->dayOfWeekIso, $selectedWeekdays, true)) {
                $dates->push($cursor);
            }

            $cursor = $cursor->addDay();
        }

        return $dates;
    }

    private function generateMonthlyDates(CarbonImmutable $startsOn, CarbonImmutable $endsOn): Collection
    {
        $dates = collect();
        $cursor = $startsOn->startOfMonth();
        $dayOfMonth = $startsOn->day;

        while ($cursor->lessThanOrEqualTo($endsOn->startOfMonth())) {
            if ($dayOfMonth <= $cursor->daysInMonth) {
                $candidate = $cursor->setDay($dayOfMonth);

                if ($candidate->greaterThanOrEqualTo($startsOn) && $candidate->lessThanOrEqualTo($endsOn)) {
                    $dates->push($candidate);
                }
            }

            $cursor = $cursor->addMonth();
        }

        return $dates;
    }
}
