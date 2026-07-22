<?php

namespace App\Models;

use App\Enums\VisitorStatus;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'id',
    'entry_at',
    'exit_at',
    'visitor_name',
    'unit_or_vendor',
    'purpose',
    'escorted_by',
    'notes',
    'status',
    'created_by',
])]
class ServerRoomVisitor extends Model
{
    use HasAttachments;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'entry_at' => 'datetime',
        'exit_at' => 'datetime',
        'status' => VisitorStatus::class,
    ];

    public function escort(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escorted_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
