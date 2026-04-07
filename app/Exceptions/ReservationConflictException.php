<?php

namespace App\Exceptions;

use App\Models\Reservation;
use Carbon\Carbon;
use DomainException;

class ReservationConflictException extends DomainException
{
    public function __construct(
        private readonly ?array $conflict = null,
        string $message = 'Conflito: já existe um agendamento nessa sala nesse horário.'
    ) {
        parent::__construct($message);
    }

    public static function forRoomAndTime(?Reservation $reservation = null): self
    {
        if ($reservation === null) {
            return new self;
        }

        $reservation->loadMissing('room');

        $conflict = [
            'room_name' => $reservation->room?->name,
            'date' => Carbon::parse($reservation->date)->format('d/m/Y'),
            'start_time' => Carbon::parse($reservation->start_time)->format('H:i'),
            'end_time' => Carbon::parse($reservation->end_time)->format('H:i'),
            'title' => $reservation->title,
            'requester' => $reservation->requester,
        ];

        $message = sprintf(
            'Conflito: sala %s já reservada em %s, de %s às %s.',
            $conflict['room_name'] ?? '(não informada)',
            $conflict['date'],
            $conflict['start_time'],
            $conflict['end_time']
        );

        return new self($conflict, $message);
    }

    public function context(): array
    {
        return $this->conflict ?? [];
    }
}
