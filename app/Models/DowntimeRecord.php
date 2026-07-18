<?php

namespace App\Models;

use App\Enums\DowntimeComponentRole;
use App\Enums\DowntimeImpact;
use App\Enums\DowntimeStatus;
use App\Enums\DowntimeType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'type',
    'reason',
    'start_time',
    'end_time',
    'duration',
    'impact',
    'reported_by',
    'location_id',
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
        'estimated_cost' => 'decimal:2',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(DowntimeLocation::class, 'location_id');
    }

    public function recordComponents(): HasMany
    {
        return $this->hasMany(DowntimeRecordComponent::class, 'downtime_id');
    }

    public function sourceComponents(): BelongsToMany
    {
        return $this->belongsToMany(
            DowntimeComponent::class,
            'downtime_record_components',
            'downtime_id',
            'component_id'
        )
            ->wherePivot('role', DowntimeComponentRole::Source->value)
            ->withTimestamps()
            ->withPivot('role');
    }

    public function affectedComponents(): BelongsToMany
    {
        return $this->belongsToMany(
            DowntimeComponent::class,
            'downtime_record_components',
            'downtime_id',
            'component_id'
        )
            ->wherePivot('role', DowntimeComponentRole::Affected->value)
            ->withTimestamps()
            ->withPivot('role');
    }

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
        if (is_null($this->end_time) || is_null($this->start_time)) {
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
