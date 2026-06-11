<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable([
    'ticket_id',
    'user_id',
    'created_at',
])]
#[WithoutIncrementing]
#[WithoutTimestamps]

class TicketWatcher extends Pivot
{
    protected $table = 'ticket_watchers';
    public $casts = [
        'created_at' => 'datetime',
    ];

    // Relations
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
