<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class ReservationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'room_id' => ['required', 'exists:rooms,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'title' => ['required', 'string', 'max:255'],
            'requester' => ['required', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.required' => 'Selecione uma sala.',
            'room_id.exists' => 'Sala invalida.',
            'date.required' => 'Informe a data.',
            'date.after_or_equal' => 'Não é permitido informar data ou horário de início que já passou.',
            'start_time.required' => 'Informe o horario de inicio.',
            'start_time.date_format' => 'Horario de inicio invalido (use HH:MM).',
            'end_time.required' => 'Informe o horario de fim.',
            'end_time.date_format' => 'Horario de fim invalido (use HH:MM).',
            'end_time.after' => 'O horario de fim deve ser apos o horario de inicio.',
            'title.required' => 'Informe o titulo do agendamento.',
            'requester.required' => 'Informe o solicitante.',
        ];
    }
}
