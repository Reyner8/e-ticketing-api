<?php

namespace App\Models;

use App\Traits\HasAttachments;
use App\Traits\HasComments;
use App\Traits\HasStatusHistory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'id',
    'title',
    'description',
    'category',
    'priority',
    'status',
    'reporter_id',
    'assigned_to_id',
    'assigned_team',
    'date_reported',
    'start_date',
    'due_date',
    'completion_date',
    'estimated_effort',
    'actual_effort',
    'sla_time_elapsed',
    'sla_time_remaining',
    'sla_breached',
    'source_ticket_id',
    'is_direct_input',
])]

class ErrorReport extends Model
{
    use HasComments, HasAttachments, HasStatusHistory;
    protected $keyType = 'string';
    public $incrementing = false;

    public const CATEGORIES = [
        'hardware',
        'network',
        'software',
    ];

    public const PRIORITIES = [
        'low',
        'medium',
        'high',
        'critical',
    ];

    public const STATUSES = [
        'pending_approval',
        'in_progress',
        'completed',
        'overdue',
    ];

    public const TEAMS = [
        'programmer',
        'network',
        'hardware',
    ];

    // Relations
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function sourceTicket()
    {
        return $this->belongsTo(Ticket::class, 'source_ticket_id');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
