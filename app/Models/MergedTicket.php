<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable([
    'parent_ticket_id',
    'merged_ticket_id',
    'merged_at',
    'merged_by',
])]
#[WithoutTimestamps]
#[WithoutIncrementing]

class MergedTicket extends Pivot
{
    public $table = 'merged_tickets';
    public $casts = [
        'merged_at' => 'datetime'
    ];

    // Relations
    public function parentTicket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'parent_ticket_id');
    }

    public function mergedTicket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'merged_ticket_id');
    }

    public function merger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merged_by');
    }
}
