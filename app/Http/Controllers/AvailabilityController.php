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
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $selectedDate = Carbon::parse($validated['date'] ?? now()->toDateString())->startOfDay();

        $rooms = Room::query()
            ->active()
            ->orderBy('name')
            ->get();

        $reservations = Reservation::query()
            ->with(['room', 'user'])
            ->whereDate('date', $selectedDate->toDateString())
            ->whereIn('room_id', $rooms->pluck('id'))
            ->orderBy('room_id')
            ->orderBy('start_time')
            ->get();

        $reservationsByRoom = $reservations->groupBy('room_id');

        $roomAvailability = $rooms->map(function (Room $room) use ($reservationsByRoom): array {
            /** @var \Illuminate\Support\Collection<int, \App\Models\Reservation> $roomReservations */
            $roomReservations = $reservationsByRoom->get($room->id, collect())->values();

            return [
                'room' => $room,
                'reservations' => $roomReservations,
                'free_ranges' => $this->buildFreeRanges($roomReservations),
                'is_free_all_day' => $roomReservations->isEmpty(),
            ];
        });

        return view('availability.index', [
            'selectedDate' => $selectedDate,
            'selectedDateLabel' => $selectedDate->translatedFormat('d/m/Y'),
            'openTime' => self::OPEN_TIME,
            'closeTime' => self::CLOSE_TIME,
            'dayReservations' => $reservations,
            'roomAvailability' => $roomAvailability,
            'freeRoomsCount' => $roomAvailability->where('is_free_all_day', true)->count(),
            'occupiedRoomsCount' => $roomAvailability->where('is_free_all_day', false)->count(),
        ]);
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
