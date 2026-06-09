<?php

namespace App\Http\Requests;

use App\Rules\WithinBusinessHours;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class ReservationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $phone = $this->normalizePhone($this->input('phone'));
        $ownerUserId = $this->user()?->canManageAgenda()
            ? $this->input('owner_user_id')
            : $this->user()?->id;

        if (! $this->has('booking_mode') || $this->input('booking_mode') === null || $this->input('booking_mode') === '') {
            $this->merge(array_filter([
                'booking_mode' => 'single',
                'phone' => $phone,
                'owner_user_id' => $ownerUserId,
            ], fn (mixed $value): bool => $value !== null));

            return;
        }

        $payload = [];

        if ($phone !== null) {
            $payload['phone'] = $phone;
        }

        if ($ownerUserId !== null && $ownerUserId !== '') {
            $payload['owner_user_id'] = $ownerUserId;
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }

    protected function baseReservationRules(): array
    {
        return [
            'room_id' => ['required', 'exists:rooms,id'],
            'start_time' => ['required', 'date_format:H:i', new WithinBusinessHours('start')],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time', new WithinBusinessHours('end')],
            'title' => ['required', 'string', 'max:255'],
            'requester' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+55 \d{2} \d{5}-\d{4}$/'],
            'owner_user_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
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
            'room_id.exists' => 'Sala inválida.',
            'date.required' => 'Informe a data.',
            'date.after_or_equal' => 'Não é permitido informar data ou horário de início que já passou.',
            'recurrence_starts_on.required' => 'Informe a data de início da recorrência.',
            'recurrence_starts_on.after_or_equal' => 'A recorrência deve iniciar hoje ou no futuro.',
            'recurrence_ends_on.required' => 'Informe a data final da recorrência.',
            'recurrence_ends_on.after_or_equal' => 'A data final deve ser igual ou posterior ao início.',
            'start_time.required' => 'Informe o horário de início.',
            'start_time.date_format' => 'Horário de início inválido (use HH:MM).',
            'end_time.required' => 'Informe o horário de fim.',
            'end_time.date_format' => 'Horário de fim inválido (use HH:MM).',
            'end_time.after' => 'O horário de fim deve ser após o horário de início.',
            'title.required' => 'Informe o título do agendamento.',
            'requester.required' => 'Informe o solicitante.',
            'phone.required' => 'Informe o telefone do solicitante.',
            'phone.regex' => 'Informe o telefone no formato +55 DDD 99999-9999.',
            'owner_user_id.required' => 'Selecione o titular da reserva.',
            'owner_user_id.exists' => 'Titular inválido.',
            'recurrence_frequency.required' => 'Selecione a frequência da recorrência.',
            'recurrence_weekdays.required' => 'Selecione ao menos um dia da semana para a recorrência semanal.',
        ];
    }

    protected function normalizePhone(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        if ($digits === null) {
            return trim($value);
        }

        if (strlen($digits) === 11) {
            $digits = '55'.$digits;
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
