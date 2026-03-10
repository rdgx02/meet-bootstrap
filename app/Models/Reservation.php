<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Reservation extends Model
{
    protected $fillable = [
        'room_id',
        'user_id', // quem criou
        'date',
        'start_time',
        'end_time',
        'title',
        'requester',
        'contact',
    ];

    /*
    |--------------------------------------------------------------------------
    | Model Events (auto auditoria)
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        // Quando atualizar um agendamento
        static::updating(function (Reservation $reservation) {

            // garante que existe usuário logado
            if (Auth::check()) {
                $reservation->updated_by = Auth::id();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Sala
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    // Usuário que CRIOU
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Usuário que EDITOU por último
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors formatados
    |--------------------------------------------------------------------------
    */

    public function getDateBrAttribute(): string
    {
        return Carbon::parse($this->date)->format('d/m/Y');
    }

    public function getStartTimeBrAttribute(): string
    {
        return Carbon::parse($this->start_time)->format('H:i');
    }

    public function getEndTimeBrAttribute(): string
    {
        return Carbon::parse($this->end_time)->format('H:i');
    }
}
