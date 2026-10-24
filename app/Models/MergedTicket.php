<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
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
    protected $table = 'merged_tickets';
    public $casts = [
        'merged_at' => 'datetime'
    ];
}
