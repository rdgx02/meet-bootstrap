<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Reservation;
use App\Models\ReservationSeries;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RecurringIntervalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * (a) Série "a cada 2 semanas": as datas geradas pulam de 2 em 2 (14 dias),
     * e as semanas ímpares não existem.
     */
    public function test_weekly_series_with_interval_two_skips_every_other_week(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 10, 8, 0, 0));

        try {
            $secretary = User::factory()->create(['role' => UserRole::Secretary]);
            $owner = User::factory()->create(['role' => UserRole::User]);
            $room = Room::create(['name' => 'Lab Quinzenal', 'is_active' => true]);

            $monday = Carbon::today()->next(Carbon::MONDAY);
            $endsOn = $monday->copy()->addWeeks(5);

            $this->actingAs($secretary)->post(route('reservations.store'), [
                'booking_mode' => 'recurring',
                'room_id' => $room->id,
                'owner_user_id' => $owner->id,
                'start_time' => '09:00',
                'end_time' => '10:00',
                'title' => 'Reuniao Quinzenal',
                'requester' => 'Lab',
                'phone' => '+55 21 99999-9999',
                'recurrence_starts_on' => $monday->toDateString(),
                'recurrence_ends_on' => $endsOn->toDateString(),
                'recurrence_frequency' => 'weekly',
                'recurrence_weekdays' => [1],
                'recurrence_interval' => 2,
            ])->assertRedirect(route('reservations.index'));

            $series = ReservationSeries::query()->where('title', 'Reuniao Quinzenal')->firstOrFail();
            $this->assertSame(2, (int) $series->interval);

            $dates = Reservation::query()
                ->where('series_id', $series->id)
                ->orderBy('date')
                ->pluck('date')
                ->map(fn (mixed $date): string => Carbon::parse($date)->toDateString())
                ->all();

            $this->assertSame([
                $monday->toDateString(),
                $monday->copy()->addWeeks(2)->toDateString(),
                $monday->copy()->addWeeks(4)->toDateString(),
            ], $dates);

            $this->assertDatabaseMissing('reservations', [
                'series_id' => $series->id,
                'date' => $monday->copy()->addWeeks(1)->toDateString(),
            ]);
            $this->assertDatabaseMissing('reservations', [
                'series_id' => $series->id,
                'date' => $monday->copy()->addWeeks(3)->toDateString(),
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * (a-borda) Série "a cada 2 semanas" que COMEÇA NO MEIO DA SEMANA (quarta),
     * com o weekday selecionado caindo na segunda seguinte. A primeira ocorrência
     * deve ser essa primeira segunda (não duas semanas depois), e a cadência segue
     * de 2 em 2 a partir dela. Borda em que a "semana âncora" pode escorregar.
     */
    public function test_weekly_interval_two_starting_midweek_anchors_on_first_occurrence(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 9, 8, 0, 0));

        try {
            $secretary = User::factory()->create(['role' => UserRole::Secretary]);
            $owner = User::factory()->create(['role' => UserRole::User]);
            $room = Room::create(['name' => 'Lab Meio de Semana', 'is_active' => true]);

            $wednesday = Carbon::today()->next(Carbon::WEDNESDAY);
            $firstMonday = $wednesday->copy()->next(Carbon::MONDAY);
            $endsOn = $firstMonday->copy()->addWeeks(4);

            $this->actingAs($secretary)->post(route('reservations.store'), [
                'booking_mode' => 'recurring',
                'room_id' => $room->id,
                'owner_user_id' => $owner->id,
                'start_time' => '09:00',
                'end_time' => '10:00',
                'title' => 'Quinzenal Meio Semana',
                'requester' => 'Lab',
                'phone' => '+55 21 99999-9999',
                'recurrence_starts_on' => $wednesday->toDateString(),
                'recurrence_ends_on' => $endsOn->toDateString(),
                'recurrence_frequency' => 'weekly',
                'recurrence_weekdays' => [1],
                'recurrence_interval' => 2,
            ])->assertRedirect(route('reservations.index'));

            $series = ReservationSeries::query()->where('title', 'Quinzenal Meio Semana')->firstOrFail();

            $dates = Reservation::query()
                ->where('series_id', $series->id)
                ->orderBy('date')
                ->pluck('date')
                ->map(fn (mixed $date): string => Carbon::parse($date)->toDateString())
                ->all();

            // Primeira ocorrência = a primeira segunda após a quarta; depois de 2 em 2.
            $this->assertSame([
                $firstMonday->toDateString(),
                $firstMonday->copy()->addWeeks(2)->toDateString(),
                $firstMonday->copy()->addWeeks(4)->toDateString(),
            ], $dates);

            // As segundas das semanas ímpares (1 e 3 a partir da primeira) não existem.
            $this->assertDatabaseMissing('reservations', [
                'series_id' => $series->id,
                'date' => $firstMonday->copy()->addWeeks(1)->toDateString(),
            ]);
            $this->assertDatabaseMissing('reservations', [
                'series_id' => $series->id,
                'date' => $firstMonday->copy()->addWeeks(3)->toDateString(),
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * (b) Intervalo fora da faixa (0 e 5) é rejeitado na validação e nenhuma
     * série é criada.
     */
    public function test_weekly_series_rejects_interval_out_of_range(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 10, 8, 0, 0));

        try {
            $secretary = User::factory()->create(['role' => UserRole::Secretary]);
            $owner = User::factory()->create(['role' => UserRole::User]);
            $room = Room::create(['name' => 'Lab Range', 'is_active' => true]);
            $monday = Carbon::today()->next(Carbon::MONDAY);

            $payload = fn (int $interval): array => [
                'booking_mode' => 'recurring',
                'room_id' => $room->id,
                'owner_user_id' => $owner->id,
                'start_time' => '09:00',
                'end_time' => '10:00',
                'title' => 'Serie Invalida',
                'requester' => 'Lab',
                'phone' => '+55 21 99999-9999',
                'recurrence_starts_on' => $monday->toDateString(),
                'recurrence_ends_on' => $monday->copy()->addWeeks(4)->toDateString(),
                'recurrence_frequency' => 'weekly',
                'recurrence_weekdays' => [1],
                'recurrence_interval' => $interval,
            ];

            $this->actingAs($secretary)
                ->from(route('reservations.create'))
                ->post(route('reservations.store'), $payload(0))
                ->assertRedirect(route('reservations.create'))
                ->assertSessionHasErrors('recurrence_interval');

            $this->actingAs($secretary)
                ->from(route('reservations.create'))
                ->post(route('reservations.store'), $payload(5))
                ->assertSessionHasErrors('recurrence_interval');

            $this->assertDatabaseMissing('reservation_series', ['title' => 'Serie Invalida']);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * (c) Série semanal (interval=1, campo omitido → default) de ~12 meses gera
     * todas as ocorrências, sem truncamento por contagem.
     */
    public function test_weekly_series_generates_full_year_without_truncation(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 10, 8, 0, 0));

        try {
            $secretary = User::factory()->create(['role' => UserRole::Secretary]);
            $owner = User::factory()->create(['role' => UserRole::User]);
            $room = Room::create(['name' => 'Lab Anual', 'is_active' => true]);

            $monday = Carbon::today()->next(Carbon::MONDAY);
            // 52 segundas (semanas 0..51); 357 dias <= 370 da janela máxima.
            $endsOn = $monday->copy()->addWeeks(51);

            $this->actingAs($secretary)->post(route('reservations.store'), [
                'booking_mode' => 'recurring',
                'room_id' => $room->id,
                'owner_user_id' => $owner->id,
                'start_time' => '09:00',
                'end_time' => '10:00',
                'title' => 'Serie Anual',
                'requester' => 'Lab',
                'phone' => '+55 21 99999-9999',
                'recurrence_starts_on' => $monday->toDateString(),
                'recurrence_ends_on' => $endsOn->toDateString(),
                'recurrence_frequency' => 'weekly',
                'recurrence_weekdays' => [1],
            ])->assertRedirect(route('reservations.index'));

            $series = ReservationSeries::query()->where('title', 'Serie Anual')->firstOrFail();
            $this->assertSame(1, (int) $series->interval);
            $this->assertSame(52, Reservation::query()->where('series_id', $series->id)->count());
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * (d1) Editar toda a série (escopo "all") preserva o intervalo: as ocorrências
     * futuras recriadas continuam de 2 em 2.
     */
    public function test_editing_series_all_scope_preserves_interval(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 10, 8, 0, 0));

        try {
            $secretary = User::factory()->create(['role' => UserRole::Secretary]);
            $owner = User::factory()->create(['role' => UserRole::User]);
            $room = Room::create(['name' => 'Lab All', 'is_active' => true]);

            $monday = Carbon::today()->next(Carbon::MONDAY);

            $this->actingAs($secretary)->post(route('reservations.store'), [
                'booking_mode' => 'recurring',
                'room_id' => $room->id,
                'owner_user_id' => $owner->id,
                'start_time' => '09:00',
                'end_time' => '10:00',
                'title' => 'Quinzenal Base',
                'requester' => 'Lab',
                'phone' => '+55 21 99999-9999',
                'recurrence_starts_on' => $monday->toDateString(),
                'recurrence_ends_on' => $monday->copy()->addWeeks(5)->toDateString(),
                'recurrence_frequency' => 'weekly',
                'recurrence_weekdays' => [1],
                'recurrence_interval' => 2,
            ])->assertRedirect(route('reservations.index'));

            $series = ReservationSeries::query()->where('title', 'Quinzenal Base')->firstOrFail();
            $first = Reservation::query()->where('series_id', $series->id)->orderBy('date')->firstOrFail();

            $this->actingAs($secretary)->put(route('reservations.update', $first), [
                'room_id' => $room->id,
                'owner_user_id' => $owner->id,
                'date' => $monday->toDateString(),
                'start_time' => '14:00',
                'end_time' => '15:00',
                'title' => 'Quinzenal Editada',
                'requester' => 'Lab',
                'phone' => '+55 21 99999-9999',
                'series_scope' => 'all',
                'from' => 'series',
                'series' => $series->id,
            ])->assertRedirect(route('reservation-series.show', $series->id));

            $series->refresh();
            $this->assertSame(2, (int) $series->interval);

            $dates = Reservation::query()
                ->where('series_id', $series->id)
                ->orderBy('date')
                ->pluck('date')
                ->map(fn (mixed $date): string => Carbon::parse($date)->toDateString())
                ->all();

            $this->assertSame([
                $monday->toDateString(),
                $monday->copy()->addWeeks(2)->toDateString(),
                $monday->copy()->addWeeks(4)->toDateString(),
            ], $dates);

            $this->assertDatabaseHas('reservations', [
                'series_id' => $series->id,
                'start_time' => '14:00',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * (d2) Editar "esta e próximas" (escopo "following") preserva o intervalo: a
     * nova continuidade da série mantém a cadência de 2 em 2 (não volta a semanal).
     */
    public function test_editing_series_following_scope_preserves_interval(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 10, 8, 0, 0));

        try {
            $secretary = User::factory()->create(['role' => UserRole::Secretary]);
            $owner = User::factory()->create(['role' => UserRole::User]);
            $room = Room::create(['name' => 'Lab Following', 'is_active' => true]);

            $monday = Carbon::today()->next(Carbon::MONDAY);

            $this->actingAs($secretary)->post(route('reservations.store'), [
                'booking_mode' => 'recurring',
                'room_id' => $room->id,
                'owner_user_id' => $owner->id,
                'start_time' => '09:00',
                'end_time' => '10:00',
                'title' => 'Quinzenal Origem',
                'requester' => 'Lab',
                'phone' => '+55 21 99999-9999',
                'recurrence_starts_on' => $monday->toDateString(),
                'recurrence_ends_on' => $monday->copy()->addWeeks(5)->toDateString(),
                'recurrence_frequency' => 'weekly',
                'recurrence_weekdays' => [1],
                'recurrence_interval' => 2,
            ])->assertRedirect(route('reservations.index'));

            $series = ReservationSeries::query()->where('title', 'Quinzenal Origem')->firstOrFail();
            $second = Reservation::query()
                ->where('series_id', $series->id)
                ->orderBy('date')
                ->skip(1)
                ->firstOrFail();

            $this->actingAs($secretary)->put(route('reservations.update', $second), [
                'room_id' => $room->id,
                'owner_user_id' => $owner->id,
                'date' => $monday->copy()->addWeeks(2)->toDateString(),
                'start_time' => '14:00',
                'end_time' => '15:00',
                'title' => 'Quinzenal Following',
                'requester' => 'Lab',
                'phone' => '+55 21 99999-9999',
                'series_scope' => 'following',
                'from' => 'series',
                'series' => $series->id,
            ])->assertRedirect(route('reservation-series.show', $series->id));

            $newSeries = ReservationSeries::query()
                ->where('title', 'Quinzenal Following')
                ->latest('id')
                ->firstOrFail();

            $this->assertSame(2, (int) $newSeries->interval);

            $dates = Reservation::query()
                ->where('series_id', $newSeries->id)
                ->orderBy('date')
                ->pluck('date')
                ->map(fn (mixed $date): string => Carbon::parse($date)->toDateString())
                ->all();

            $this->assertSame([
                $monday->copy()->addWeeks(2)->toDateString(),
                $monday->copy()->addWeeks(4)->toDateString(),
            ], $dates);
        } finally {
            Carbon::setTestNow();
        }
    }
}
