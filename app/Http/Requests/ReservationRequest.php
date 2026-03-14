<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class ReservationRequest extends FormRequest
{
    protected function baseReservationRules(): array
    {
        return [
            'room_id' => ['required', 'exists:rooms,id'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'title' => ['required', 'string', 'max:255'],
            'requester' => ['required', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function singleReservationRules(): array
    {
        return [
            ...$this->baseReservationRules(),
            'date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    protected function recurringReservationRules(): array
    {
        return [
            ...$this->baseReservationRules(),
            'booking_mode' => ['required', Rule::in(['single', 'recurring'])],
            'date' => [
                Rule::requiredIf(fn (): bool => $this->input('booking_mode', 'single') === 'single'),
                'nullable',
                'date',
                'after_or_equal:today',
            ],
            'recurrence_starts_on' => [
                Rule::requiredIf(fn (): bool => $this->input('booking_mode') === 'recurring'),
                'nullable',
                'date',
                'after_or_equal:today',
            ],
            'recurrence_ends_on' => [
                Rule::requiredIf(fn (): bool => $this->input('booking_mode') === 'recurring'),
                'nullable',
                'date',
                'after_or_equal:recurrence_starts_on',
            ],
            'recurrence_frequency' => [
                Rule::requiredIf(fn (): bool => $this->input('booking_mode') === 'recurring'),
                'nullable',
                Rule::in(['daily', 'weekly', 'monthly']),
            ],
            'recurrence_weekdays' => [
                Rule::requiredIf(fn (): bool => $this->input('booking_mode') === 'recurring'
                    && $this->input('recurrence_frequency') === 'weekly'),
                'nullable',
                'array',
            ],
            'recurrence_weekdays.*' => ['integer', Rule::in([1, 2, 3, 4, 5, 6, 7])],
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.required' => 'Selecione uma sala.',
            'room_id.exists' => 'Sala invalida.',
            'date.required' => 'Informe a data.',
            'date.after_or_equal' => 'Não é permitido informar data ou horário de início que já passou.',
            'recurrence_starts_on.required' => 'Informe a data de inicio da recorrencia.',
            'recurrence_starts_on.after_or_equal' => 'A recorrencia deve iniciar hoje ou no futuro.',
            'recurrence_ends_on.required' => 'Informe a data final da recorrencia.',
            'recurrence_ends_on.after_or_equal' => 'A data final deve ser igual ou posterior ao inicio.',
            'start_time.required' => 'Informe o horario de inicio.',
            'start_time.date_format' => 'Horario de inicio invalido (use HH:MM).',
            'end_time.required' => 'Informe o horario de fim.',
            'end_time.date_format' => 'Horario de fim invalido (use HH:MM).',
            'end_time.after' => 'O horario de fim deve ser apos o horario de inicio.',
            'title.required' => 'Informe o titulo do agendamento.',
            'requester.required' => 'Informe o solicitante.',
            'recurrence_frequency.required' => 'Selecione a frequencia da recorrencia.',
            'recurrence_weekdays.required' => 'Selecione ao menos um dia da semana para a recorrencia semanal.',
        ];
    }
}
