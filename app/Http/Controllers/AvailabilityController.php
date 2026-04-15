<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Room;
use App\Services\AvailabilityOverviewService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AvailabilityController extends Controller
{
    public function __construct(
        private readonly AvailabilityOverviewService $availabilityOverview,
    ) {
    }

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
            'openTime' => '08:00',
            'closeTime' => '18:00',
            'dayReservations' => $reservations,
            'rooms' => $rooms,
            'roomAvailability' => $roomAvailability,
            'primaryAvailability' => $primaryAvailability,
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

        $roomAvailability = $this->availabilityOverview->summarize($rooms, $reservations, $selectedRoom);

        return [$selectedDate, $rooms, $reservations, $roomAvailability, $selectedRoom];
    }
}
