<?php

namespace App\Http\Requests;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Validation\Validator;

class StoreReservationRequest extends ReservationRequest
{
    public function rules(): array
    {
        return $this->recurringReservationRules();
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create', Reservation::class) ?? false;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->input('booking_mode', 'single') === 'single') {
                $date = (string) $this->input('date');

                if ($date !== now()->toDateString()) {
                    return;
                }

                $startAt = Carbon::parse(sprintf('%s %s', $date, $this->input('start_time')));

                if ($startAt->lessThanOrEqualTo(now())) {
                    $validator->errors()->add('start_time', 'Não é permitido informar data ou horário de início que já passou.');
                }

                return;
            }

            $startsOn = (string) $this->input('recurrence_starts_on');
            $endsOn = (string) $this->input('recurrence_ends_on');

            if ($startsOn === '' || $endsOn === '') {
                return;
            }

            if ($startsOn === now()->toDateString()) {
                $startAt = Carbon::parse(sprintf('%s %s', $startsOn, $this->input('start_time')));

                if ($startAt->lessThanOrEqualTo(now())) {
                    $validator->errors()->add('start_time', 'A primeira ocorrencia da recorrencia nao pode iniciar no passado.');
                }
            }

            if (Carbon::parse($startsOn)->diffInDays(Carbon::parse($endsOn)) > 370) {
                $validator->errors()->add('recurrence_ends_on', 'O periodo da recorrencia deve ter no maximo 12 meses.');
            }
        });
    }
}
