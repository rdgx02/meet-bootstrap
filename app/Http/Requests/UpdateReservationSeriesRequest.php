<?php

namespace App\Http\Requests;

use App\Models\ReservationSeries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateReservationSeriesRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $phone = $this->normalizePhone($this->input('phone'));

        if ($phone !== null) {
            $this->merge([
                'phone' => $phone,
            ]);
        }
    }

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
            'phone' => ['required', 'string', 'regex:/^\+55 \d{2} \d{5}-\d{4}$/'],
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
                $validator->errors()->add('recurrence_ends_on', 'O período da recorrência deve ter no máximo 12 meses.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'room_id.required' => 'Selecione uma sala.',
            'room_id.exists' => 'Sala inválida.',
            'title.required' => 'Informe o título do agendamento.',
            'requester.required' => 'Informe o solicitante.',
            'phone.required' => 'Informe o telefone do solicitante.',
            'phone.regex' => 'Informe o telefone no formato +55 DDD 99999-9999.',
            'start_time.required' => 'Informe o horário de início.',
            'start_time.date_format' => 'Horário de início inválido (use HH:MM).',
            'end_time.required' => 'Informe o horário de fim.',
            'end_time.date_format' => 'Horário de fim inválido (use HH:MM).',
            'end_time.after' => 'O horário de fim deve ser após o horário de início.',
            'recurrence_starts_on.required' => 'Informe a data de início da recorrência.',
            'recurrence_ends_on.required' => 'Informe a data final da recorrência.',
            'recurrence_ends_on.after_or_equal' => 'A data final deve ser igual ou posterior ao início.',
            'recurrence_frequency.required' => 'Selecione a frequência da recorrência.',
            'recurrence_weekdays.required' => 'Selecione ao menos um dia da semana para a recorrência semanal.',
        ];
    }

    private function normalizePhone(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        if ($digits === null) {
            return trim($value);
        }

        if (strlen($digits) === 11) {
            $digits = '55' . $digits;
        }

        if (strlen($digits) !== 13 || ! str_starts_with($digits, '55')) {
            return trim($value);
        }

        return sprintf(
            '+55 %s %s-%s',
            substr($digits, 2, 2),
            substr($digits, 4, 5),
            substr($digits, 9, 4)
        );
    }
}
