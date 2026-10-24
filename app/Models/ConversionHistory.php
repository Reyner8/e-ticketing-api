<?php

namespace App\Models;

use App\Enums\ConversionTypes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'source_ticket_id',
    'target_type',
    'target_id',
    'converted_by',
    'converted_at',
    'reason',
    'notes',]
)]

#[WithoutTimestamps]

class ConversionHistory extends Model
{
    protected $casts = [
        'converted_at' => 'datetime',
        'target_type' => ConversionTypes::class,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->converted_at)) {
                $model->converted_at = now();
            }
        });
    }

    // Relations
    public function sourceTicket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'source_ticket_id');
    }

    public function converter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by');
    }
}
