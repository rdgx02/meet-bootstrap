<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class RecurringReservationOccurrenceGenerator
{
    public function generate(array $data): Collection
    {
        $startsOn = CarbonImmutable::parse($data['recurrence_starts_on']);
        $endsOn = CarbonImmutable::parse($data['recurrence_ends_on']);
        $frequency = (string) $data['recurrence_frequency'];
        $interval = max(1, (int) ($data['recurrence_interval'] ?? 1));

        $dates = match ($frequency) {
            'daily' => $this->generateDailyDates($startsOn, $endsOn, $interval),
            'weekly' => $this->generateWeeklyDates($startsOn, $endsOn, $data['recurrence_weekdays'] ?? [], $interval),
            'monthly' => $this->generateMonthlyDates($startsOn, $endsOn, $interval),
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
                'phone' => (string) $data['phone'],
                'owner_user_id' => (int) $data['owner_user_id'],
            ];
        });
    }

    private function generateDailyDates(CarbonImmutable $startsOn, CarbonImmutable $endsOn, int $interval = 1): Collection
    {
        $dates = collect();
        $cursor = $startsOn;

        while ($cursor->lessThanOrEqualTo($endsOn)) {
            $dates->push($cursor);
            $cursor = $cursor->addDays(max(1, $interval));
        }

        return $dates;
    }

    private function generateWeeklyDates(CarbonImmutable $startsOn, CarbonImmutable $endsOn, array $weekdays, int $interval): Collection
    {
        $selectedWeekdays = collect($weekdays)
            ->map(fn (mixed $weekday): int => (int) $weekday)
            ->filter(fn (int $weekday): bool => in_array($weekday, [1, 2, 3, 4, 5, 6, 7], true))
            ->unique()
            ->sort()
            ->values()
            ->all();

        // Âncora = a semana (segunda a domingo) da PRIMEIRA ocorrência gerada, não a
        // de starts_on: a série conta de N em N a partir da primeira vez que de fato
        // acontece. Assim um início no meio da semana (ex.: quarta) com o weekday
        // caindo na segunda seguinte não perde a primeira ocorrência. Uma ocorrência
        // só entra se o índice da sua semana for múltiplo do intervalo (interval=1
        // reproduz exatamente o comportamento de toda-semana).
        $anchorWeek = null;

        $dates = collect();
        $cursor = $startsOn;

        while ($cursor->lessThanOrEqualTo($endsOn)) {
            if (in_array($cursor->dayOfWeekIso, $selectedWeekdays, true)) {
                $anchorWeek ??= $cursor->startOfWeek(CarbonInterface::MONDAY);
                $weekIndex = (int) $anchorWeek->diffInWeeks($cursor->startOfWeek(CarbonInterface::MONDAY));

                if ($weekIndex % $interval === 0) {
                    $dates->push($cursor);
                }
            }

            $cursor = $cursor->addDay();
        }

        return $dates;
    }

    private function generateMonthlyDates(CarbonImmutable $startsOn, CarbonImmutable $endsOn, int $interval = 1): Collection
    {
        $interval = max(1, $interval);
        $dates = collect();
        $cursor = $startsOn->startOfMonth();
        $dayOfMonth = $startsOn->day;
        $monthIndex = 0;

        while ($cursor->lessThanOrEqualTo($endsOn->startOfMonth())) {
            if ($monthIndex % $interval === 0) {
                // "Clampa" para o último dia quando o mês não tem o dia-âncora
                // (ex.: série no dia 31 cai em 28/02, 30/04...). Assim nenhuma
                // ocorrência é perdida silenciosamente em meses curtos.
                $targetDay = min($dayOfMonth, $cursor->daysInMonth);
                $candidate = $cursor->setDay($targetDay);

                if ($candidate->greaterThanOrEqualTo($startsOn) && $candidate->lessThanOrEqualTo($endsOn)) {
                    $dates->push($candidate);
                }
            }

            $cursor = $cursor->addMonth();
            $monthIndex++;
        }

        return $dates;
    }
}
