<?php

namespace App\Models;

use App\Enums\TimelinePhase;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'feature_request_id',
    'phase',
    'title',
    'description',
    'start_date',
    'end_date',
    'is_completed',
    'progress',
    'assigned_to',
    'notes'
])]

#[WithoutTimestamps]

class TimelineEntry extends Model
{
    protected $casts = [
        'phase' => TimelinePhase::class,
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_completed' => 'boolean',
        'progress' => 'integer'
    ];
    
    // Relations
    public function featureRequest(): BelongsTo
    {
        return $this->belongsTo(FeatureRequest::class, 'feature_request_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Helpers
    public function isCompleted(): bool
    {
        return $this->is_completed === TimelinePhase::Completion->value;
    }

    public function isOverdue(): bool
    {
        if (! $this->isCompleted() || ! $this->after_date) {
            return false;
        }

        return now()->isAfter($this->after_date);
    }

    public function getDurationByDays(): ?int
    {
        if (is_null($this->start_date) || is_null($this->end_date)) {
            return null;
        }
        
        return (int) $this->start_date->diffInDays($this->end_date);
    }

}
