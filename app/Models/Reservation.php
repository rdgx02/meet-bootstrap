<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Reservation extends Model
{
    protected $fillable = [
        'room_id',
        'series_id',
        'user_id', // quem criou
        'owner_user_id', // para quem a reserva foi criada
        'date',
        'original_date',
        'is_exception',
        'start_time',
        'end_time',
        'title',
        'requester',
        'phone',
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

    public function series(): BelongsTo
    {
        return $this->belongsTo(ReservationSeries::class, 'series_id');
    }

    // Usuário que CRIOU
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
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

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user === null || $user->canManageAgenda()) {
            return $query;
        }

        return $query->where(function (Builder $ownedQuery) use ($user): void {
            $ownedQuery->where('owner_user_id', $user->id)
                ->orWhere(function (Builder $fallbackQuery) use ($user): void {
                    $fallbackQuery->whereNull('owner_user_id')
                        ->where('user_id', $user->id);
                });
        });
    }
}
