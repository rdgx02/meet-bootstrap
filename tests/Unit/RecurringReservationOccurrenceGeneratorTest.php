<?php

namespace Tests\Unit;

use App\Services\RecurringReservationOccurrenceGenerator;
use Tests\TestCase;

class RecurringReservationOccurrenceGeneratorTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<int, string>
     */
    private function dates(array $overrides): array
    {
        $data = array_merge([
            'room_id' => 1,
            'start_time' => '14:00',
            'end_time' => '15:00',
            'title' => 'X',
            'requester' => 'Y',
            'phone' => '',
            'owner_user_id' => 1,
            'recurrence_frequency' => 'monthly',
            'recurrence_interval' => 1,
            'recurrence_weekdays' => [],
        ], $overrides);

        return (new RecurringReservationOccurrenceGenerator)
            ->generate($data)
            ->pluck('date')
            ->all();
    }

    public function test_monthly_clamps_to_last_day_on_short_months(): void
    {
        $dates = $this->dates([
            'recurrence_frequency' => 'monthly',
            'recurrence_starts_on' => '2026-01-31',
            'recurrence_ends_on' => '2026-04-30',
        ]);

        // Antes, fevereiro e abril sumiam silenciosamente; agora "clampam".
        $this->assertSame(['2026-01-31', '2026-02-28', '2026-03-31', '2026-04-30'], $dates);
    }

    public function test_monthly_honors_interval_of_two_months(): void
    {
        $dates = $this->dates([
            'recurrence_frequency' => 'monthly',
            'recurrence_interval' => 2,
            'recurrence_starts_on' => '2026-01-15',
            'recurrence_ends_on' => '2026-07-31',
        ]);

        $this->assertSame(['2026-01-15', '2026-03-15', '2026-05-15', '2026-07-15'], $dates);
    }

    public function test_daily_honors_interval(): void
    {
        $dates = $this->dates([
            'recurrence_frequency' => 'daily',
            'recurrence_interval' => 2,
            'recurrence_starts_on' => '2026-01-01',
            'recurrence_ends_on' => '2026-01-07',
        ]);

        $this->assertSame(['2026-01-01', '2026-01-03', '2026-01-05', '2026-01-07'], $dates);
    }
}
