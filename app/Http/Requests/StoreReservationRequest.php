<?php

namespace App\Http\Requests;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Validation\Validator;

class StoreReservationRequest extends ReservationRequest
{
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

            $date = (string) $this->input('date');

            if ($date !== now()->toDateString()) {
                return;
            }

            $startAt = Carbon::parse(sprintf('%s %s', $date, $this->input('start_time')));

            if ($startAt->lessThanOrEqualTo(now())) {
                $validator->errors()->add('start_time', 'Não é permitido informar data ou horário de início que já passou.');
            }
        });
    }
}
