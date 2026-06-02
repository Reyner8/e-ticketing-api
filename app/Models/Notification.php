<?php

namespace App\Models;

use App\Enums\NotificationType;
use App\Enums\Priorities;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

#[Fillable([
    'type',
    'title',
    'message',
    'user_id',
    'ticket_id',
    'downtime_id',
    'is_read',
    'created_at',
    'action_url',
    'priority'
])]

#[WithoutTimestamps]

class Notification extends Model
{
    protected $casts = [
        'type' => NotificationType::class,
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'priority' => Priorities::class,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function downtime(): BelongsTo
    {
        return $this->belongsTo(DowntimeRecord::class, 'downtime_id');
    }

    // Scopes
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }
    
    public function scopeRead(Builder $query): Builder
    {
        return $query->where('is_read', true);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
