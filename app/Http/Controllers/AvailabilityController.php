<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AvailabilityController extends Controller
{
    private const OPEN_TIME = '08:00';

    private const CLOSE_TIME = '18:00';

    public function index(Request $request): View
    {
        [$selectedDate, $rooms, $reservations, $roomAvailability, $selectedRoom] = $this->buildAvailabilityViewData($request);
        $primaryAvailability = $selectedRoom instanceof Room
            ? $roomAvailability->firstWhere('room.id', $selectedRoom->id)
            : null;

        return view('availability.index', [
            'selectedDate' => $selectedDate,
            'selectedDateLabel' => $selectedDate->translatedFormat('d/m/Y'),
            'selectedRoom' => $selectedRoom,
            'openTime' => self::OPEN_TIME,
            'closeTime' => self::CLOSE_TIME,
            'dayReservations' => $reservations,
            'rooms' => $rooms,
            'roomAvailability' => $roomAvailability,
            'primaryAvailability' => $primaryAvailability,
            'freeRoomsCount' => $roomAvailability->where('is_free_all_day', true)->count(),
            'occupiedRoomsCount' => $roomAvailability->where('is_free_all_day', false)->count(),
        ]);
    }

    /**
     * @return array{0: Carbon, 1: \Illuminate\Support\Collection<int, Room>, 2: \Illuminate\Support\Collection<int, Reservation>, 3: \Illuminate\Support\Collection<int, array{room: Room, reservations: \Illuminate\Support\Collection<int, Reservation>, free_ranges: array<int, array{start: string, end: string, label: string}>, occupied_ranges: array<int, array{start: string, end: string, label: string, title: string, requester: string}>, is_free_all_day: bool, status: string, status_label: string}>, 4: ?Room}
     */
    private function buildAvailabilityViewData(Request $request): array
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
        ]);

        $selectedDate = Carbon::parse($validated['date'] ?? now()->toDateString())->startOfDay();

        $rooms = Room::query()
            ->active()
            ->orderBy('name')
            ->get();

        $selectedRoom = $rooms->firstWhere('id', (int) ($validated['room_id'] ?? 0));

        $reservations = Reservation::query()
            ->with(['room', 'user'])
            ->whereDate('date', $selectedDate->toDateString())
            ->whereIn('room_id', $rooms->pluck('id'))
            ->when($selectedRoom instanceof Room, fn ($query) => $query->where('room_id', $selectedRoom->id))
            ->orderBy('room_id')
            ->orderBy('start_time')
            ->get();

        $reservationsByRoom = $reservations->groupBy('room_id');

        $roomAvailability = $rooms->map(function (Room $room) use ($reservationsByRoom): array {
            /** @var \Illuminate\Support\Collection<int, \App\Models\Reservation> $roomReservations */
            $roomReservations = $reservationsByRoom->get($room->id, collect())->values();

            $status = $this->resolveStatus($roomReservations);

            return [
                'room' => $room,
                'reservations' => $roomReservations,
                'free_ranges' => $this->buildFreeRanges($roomReservations),
                'occupied_ranges' => $this->buildOccupiedRanges($roomReservations),
                'is_free_all_day' => $roomReservations->isEmpty(),
                'status' => $status,
                'status_label' => $this->resolveStatusLabelFromStatus($status),
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

        return [$selectedDate, $rooms, $reservations, $roomAvailability, $selectedRoom];
    }

    /**
     * @param Collection<int, Reservation> $reservations
     * @return array<int, array{start: string, end: string, label: string, title: string, requester: string}>
     */
    private function buildOccupiedRanges(Collection $reservations): array
    {
        return $reservations->map(function (Reservation $reservation): array {
            return [
                'start' => Carbon::parse($reservation->start_time)->format('H:i'),
                'end' => Carbon::parse($reservation->end_time)->format('H:i'),
                'label' => sprintf('%s as %s', $reservation->start_time_br, $reservation->end_time_br),
                'title' => $reservation->title,
                'requester' => $reservation->requester,
            ];
        })->all();
    }

    /**
     * @param Collection<int, Reservation> $reservations
     */
    private function resolveStatus(Collection $reservations): string
    {
        if ($reservations->isEmpty()) {
            return 'free';
        }

        $freeRanges = $this->buildFreeRanges($reservations);

        return $freeRanges === [] ? 'busy' : 'partial';
    }

    /**
     * @param Collection<int, Reservation> $reservations
     */
    private function resolveStatusLabel(Collection $reservations): string
    {
        return $this->resolveStatusLabelFromStatus($this->resolveStatus($reservations));
    }

    private function resolveStatusLabelFromStatus(string $status): string
    {
        return match ($status) {
            'free' => 'Livre',
            'busy' => 'Ocupada',
            default => 'Parcialmente ocupada',
        };
    }

    private function statusPriority(string $status): int
    {
        return match ($status) {
            'free' => 0,
            'partial' => 1,
            'busy' => 2,
            default => 3,
        };
    }

    /**
     * @param Collection<int, Reservation> $reservations
     * @return array<int, array{start: string, end: string, label: string}>
     */
    private function buildFreeRanges(Collection $reservations): array
    {
        $cursor = self::OPEN_TIME;
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

        if ($cursor < self::CLOSE_TIME) {
            $ranges[] = $this->makeRange($cursor, self::CLOSE_TIME);
        }

        return $ranges;
    }

    /**
     * @return array{start: string, end: string, label: string}
     */
    private function makeRange(string $start, string $end): array
    {
        return [
            'start' => $start,
            'end' => $end,
            'label' => sprintf('%s as %s', $start, $end),
        ];
    }
}
