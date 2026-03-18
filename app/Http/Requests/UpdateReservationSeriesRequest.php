<?php

namespace App\Http\Requests;

use App\Models\ReservationSeries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateReservationSeriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $series = $this->route('reservationSeries');

        return $series instanceof ReservationSeries
            && ($this->user()?->can('update', $series) ?? false);
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'exists:rooms,id'],
            'title' => ['required', 'string', 'max:255'],
            'requester' => ['required', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'recurrence_starts_on' => ['required', 'date'],
            'recurrence_ends_on' => ['required', 'date', 'after_or_equal:recurrence_starts_on', 'after_or_equal:today'],
            'recurrence_frequency' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
            'recurrence_weekdays' => [
                Rule::requiredIf(fn (): bool => $this->input('recurrence_frequency') === 'weekly'),
                'nullable',
                'array',
            ],
            'recurrence_weekdays.*' => ['integer', Rule::in([1, 2, 3, 4, 5, 6, 7])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $startsOn = (string) $this->input('recurrence_starts_on');
            $endsOn = (string) $this->input('recurrence_ends_on');

            if ($startsOn === '' || $endsOn === '') {
                return;
            }

            if (\Carbon\Carbon::parse($startsOn)->diffInDays(\Carbon\Carbon::parse($endsOn)) > 370) {
                $validator->errors()->add('recurrence_ends_on', 'O periodo da recorrencia deve ter no maximo 12 meses.');
            }
        });
    }
}
