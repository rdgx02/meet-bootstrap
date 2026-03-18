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
        ];
    }
}
