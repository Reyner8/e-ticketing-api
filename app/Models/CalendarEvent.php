<?php

namespace App\Models;

use App\Enums\EventTypes;
use App\Enums\RecurringFreq;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'title',
    'start',
    'end',
    'type',
    'description',
    'color',
    'all_day',
    'recurring_frequency',
    'recurring_interval',
    'recurring_end_date',
    'created_by',
    'created_at'
])]
#[WithoutTimestamps]

class CalendarEvent extends Model
{
    public $casts = [
        'recurring_frequency' => RecurringFreq::class,
        'type' => EventTypes::class,
        'start' => 'datetime',
        'end' => 'datetime',
        'recurring_interval' => 'integer',
        'recurring_end_date' => 'datetime',
        'created_at' => 'datetime'
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->created_at)) {
                $model->created_at = now();
            }

            if (empty($model->color) && $model->type instanceof EventTypes) {
                $model->color = $model->type->defaultColor();
            }
        });
    }

    // Relations
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Helpers
    public function isRecurring(): bool
    {
        return ! is_null($this->recurring_frequency);
    }

    public function isAllDay(): bool
    {
        return $this->all_day === true;
    }

    public function getDurationMinutesAttribute(): int
    {
        return (int) $this->start->diffInMinutes($this->end);
    }

    // Scopes
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeInRange(Builder $query, string $from, string $to): Builder
    {
        return $query->where('start', '>=', $from)
            ->where('end', '<=', $to);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start', '>=', now());
    }

    public function scopeRecurring(Builder $query): Builder
    {
        return $query->whereNotNull('recurring_frequency');
    }
}
