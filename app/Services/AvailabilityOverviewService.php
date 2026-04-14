<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AvailabilityOverviewService
{
    public function __construct(
        private readonly string $openTime = '08:00',
        private readonly string $closeTime = '18:00',
    ) {
    }

    /**
     * @param Collection<int, Room> $rooms
     * @param Collection<int, Reservation> $reservations
     * @return Collection<int, array{room: Room, reservations: Collection<int, Reservation>, free_ranges: array<int, array{start: string, end: string, label: string}>, occupied_ranges: array<int, array{start: string, end: string, label: string, title: string, requester: string}>, is_free_all_day: bool, status: string, status_label: string, free_summary: string, occupied_summary: string}>
     */
    public function summarize(Collection $rooms, Collection $reservations, ?Room $selectedRoom = null): Collection
    {
        $reservationsByRoom = $reservations->groupBy('room_id');

        return $rooms->map(function (Room $room) use ($reservationsByRoom): array {
            /** @var Collection<int, Reservation> $roomReservations */
            $roomReservations = $reservationsByRoom->get($room->id, collect())->values();
            $status = $this->resolveStatus($roomReservations);
            $freeRanges = $this->buildFreeRanges($roomReservations);
            $occupiedRanges = $this->buildOccupiedRanges($roomReservations);

            return [
                'room' => $room,
                'reservations' => $roomReservations,
                'free_ranges' => $freeRanges,
                'occupied_ranges' => $occupiedRanges,
                'is_free_all_day' => $roomReservations->isEmpty(),
                'status' => $status,
                'status_label' => $this->resolveStatusLabelFromStatus($status),
                'free_summary' => $this->buildFreeSummary($freeRanges),
                'occupied_summary' => $this->buildOccupiedSummary($occupiedRanges),
            ];
        })->when(
            $selectedRoom instanceof Room,
            fn (Collection $collection) => $collection->where('room.id', $selectedRoom->id)->values(),
            fn (Collection $collection) => $collection
                ->sortBy(fn (array $entry): string => sprintf(
                    '%d-%s',
                    $this->statusPriority($entry['status']),
                    mb_strtolower($entry['room']->name)
                ))
                ->values()
        );
    }

    /**
     * @param Collection<int, Reservation> $reservations
     * @return array<int, array{start: string, end: string, label: string}>
     */
    public function buildFreeRanges(Collection $reservations): array
    {
        $cursor = $this->openTime;
        $ranges = [];

        foreach ($reservations as $reservation) {
            $reservationStart = Carbon::parse($reservation->start_time)->format('H:i');
            $reservationEnd = Carbon::parse($reservation->end_time)->format('H:i');

            if ($cursor < $reservationStart) {
                $ranges[] = $this->makeRange($cursor, $reservationStart);
            }

            if ($reservationEnd > $cursor) {
                $cursor = $reservationEnd;
            }
        }

        if ($cursor < $this->closeTime) {
            $ranges[] = $this->makeRange($cursor, $this->closeTime);
        }

        return $ranges;
    }

    /**
     * @param Collection<int, Reservation> $reservations
     * @return array<int, array{start: string, end: string, label: string, title: string, requester: string}>
     */
    public function buildOccupiedRanges(Collection $reservations): array
    {
        return $reservations->map(function (Reservation $reservation): array {
            return [
                'start' => Carbon::parse($reservation->start_time)->format('H:i'),
                'end' => Carbon::parse($reservation->end_time)->format('H:i'),
                'label' => sprintf('%s às %s', $reservation->start_time_br, $reservation->end_time_br),
                'title' => $reservation->title,
                'requester' => $reservation->requester,
            ];
        })->all();
    }

    /**
     * @param Collection<int, Reservation> $reservations
     */
    public function resolveStatus(Collection $reservations): string
    {
        if ($reservations->isEmpty()) {
            return 'free';
        }

        return $this->buildFreeRanges($reservations) === [] ? 'busy' : 'partial';
    }

    public function resolveStatusLabelFromStatus(string $status): string
    {
        return match ($status) {
            'free' => 'Livre',
            'busy' => 'Ocupada',
            default => 'Parcialmente ocupada',
        };
    }

    public function statusPriority(string $status): int
    {
        return match ($status) {
            'free' => 0,
            'partial' => 1,
            'busy' => 2,
            default => 3,
        };
    }

    /**
     * @return array{start: string, end: string, label: string}
     */
    private function makeRange(string $start, string $end): array
    {
        return [
            'start' => $start,
            'end' => $end,
            'label' => sprintf('%s às %s', $start, $end),
        ];
    }

    /**
     * @param array<int, array{start: string, end: string, label: string}> $ranges
     */
    private function buildFreeSummary(array $ranges): string
    {
        if ($ranges === []) {
            return 'Sem faixas livres na janela consultiva.';
        }

        return collect($ranges)
            ->pluck('label')
            ->implode(' • ');
    }

    /**
     * @param array<int, array{start: string, end: string, label: string, title: string, requester: string}> $ranges
     */
    private function buildOccupiedSummary(array $ranges): string
    {
        if ($ranges === []) {
            return 'Nenhuma reserva registrada no dia.';
        }

        return collect($ranges)
            ->map(fn (array $range): string => sprintf('%s — %s', $range['label'], $range['title']))
            ->implode(' • ');
    }
}
