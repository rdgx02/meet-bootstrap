<?php

namespace App\Http\Requests;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateReservationRequest extends ReservationRequest
{
    public function rules(): array
    {
        return [
            ...$this->singleReservationRules(),
            'series_scope' => [
                Rule::requiredIf(fn (): bool => ($this->route('reservation') instanceof Reservation)
                    && $this->route('reservation')->series_id !== null),
                'nullable',
                Rule::in(['occurrence', 'following', 'all']),
            ],
        ];
    }

    public function authorize(): bool
    {
        $reservation = $this->route('reservation');

        return $reservation instanceof Reservation
            && ($this->user()?->can('update', $reservation) ?? false);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $reservation = $this->route('reservation');
            $date = (string) $this->input('date');

            if ($date !== now()->toDateString()) {
                return;
            }

            $startAt = Carbon::parse(sprintf('%s %s', $date, $this->input('start_time')));
            $endAt = Carbon::parse(sprintf('%s %s', $date, $this->input('end_time')));

            $startChanged = ! ($reservation instanceof Reservation)
                || $reservation->date !== $date
                || Carbon::parse($reservation->start_time)->format('H:i') !== (string) $this->input('start_time');

            if ($startChanged && $startAt->lessThanOrEqualTo(now())) {
                $validator->errors()->add('start_time', 'Não é permitido informar data ou horário de início que já passou.');
            }

            if ($endAt->lessThanOrEqualTo(now())) {
                $validator->errors()->add('end_time', 'Não é permitido salvar reserva que já terminou.');
            }
        });
    }
}
