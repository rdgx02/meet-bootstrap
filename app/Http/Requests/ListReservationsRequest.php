<?php

namespace App\Http\Requests;

use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;

class ListReservationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Reservation::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'in:20,50,100'],
            'code' => ['nullable', 'string', 'max:20'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'requester' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'user_name' => ['nullable', 'string', 'max:255'],
            'editor_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
