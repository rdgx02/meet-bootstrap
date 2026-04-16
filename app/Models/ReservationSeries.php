<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReservationSeries extends Model
{
    protected $fillable = [
        'room_id',
        'user_id',
        'starts_on',
        'ends_on',
        'start_time',
        'end_time',
        'title',
        'requester',
        'phone',
        'contact',
        'frequency',
        'interval',
        'weekdays',
        'conflict_mode',
        'status',
    ];

    protected $casts = [
        'weekdays' => 'array',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'series_id');
    }

    public function getStartsOnBrAttribute(): string
    {
        return Carbon::parse($this->starts_on)->format('d/m/Y');
    }

    public function getEndsOnBrAttribute(): string
    {
        return Carbon::parse($this->ends_on)->format('d/m/Y');
    }

    public function getFrequencyLabelAttribute(): string
    {
        return match ($this->frequency) {
            'daily' => 'Diaria',
            'weekly' => 'Semanal',
            'monthly' => 'Mensal',
            default => ucfirst((string) $this->frequency),
        };
    }
}
