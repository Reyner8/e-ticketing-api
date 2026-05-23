<?php

namespace App\Models;

use App\Enums\DowntimeImpact;
use App\Enums\DowntimeStatus;
use App\Enums\DowntimeType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'title',
    'type',
    'reason',
    'start_time',
    'end_time',
    'duration',
    'impact',
    'reported_by',
    'description',
    'status',
    'root_cause',
    'preventive_measures',
    'affected_users',
    'estimated_cost',
])]

class DowntimeRecord extends Model
{
    protected $casts = [
        'type' => DowntimeType::class,
        'impact' => DowntimeImpact::class,
        'status' => DowntimeStatus::class,
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration' => 'integer',
        'affected_users' => 'integer',
        'estimated_costs' => 'decimal:2',
    ];

    // Relations
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    // Helpers
    public function isOngoing(): bool
    {
        return $this->status === DowntimeStatus::Ongoing;
    }

    public function isResolved(): bool
    {
        return $this->status === DowntimeStatus::Resolved;
    }

    public function calculateDuration(): ?int
    {
        if (is_null($this->end_time)) {
            return null;
        }

        return (int) $this->start_time->diffInMinutes($this->end_time);
    }

    public function getFormattedDurationAttribute(): ?string
    {
        if (is_null($this->duration)) {
            return null;
        }

        $hours = intdiv($this->duration, 60);
        $minutes = $this->duration % 60;


        if ($hours > 0 && $minutes > 0) {
            return "{$hours} hours {$minutes} minutes";
        }

        if ($hours > 0) {
            return "{$hours} hours";
        }

        return "{$minutes} minutes";
    }
}
