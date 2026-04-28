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
    'request_type',
    'priority',
    'status',
    'progress',
    'reporter_id',
    'assigned_to_id',
    'assigned_team',
    'date_submitted',
    'approval_date',
    'assigned_date',
    'start_date',
    'due_date',
    'completion_date',
    'review_date',
    'estimated_effort',
    'actual_effort',
    'sla_time_elapsed',
    'sla_time_remaining',
    'sla_breached',
    'approved_by',
    'rejection_reason',
    'roi_impact',
    'quality_impact',
    'post_implementation_notes',
    'source_ticket_id',
    'is_direct_input',
])]

class FeatureRequest extends Model
{
    use HasComments, HasAttachments, HasStatusHistory;
    protected $keyType = 'string';
    public $incrementing = false;

    public const REQUEST_TYPES = [
        'feature_request',
        'bug_fix',
    ];

    public const PRIORITIES = [
        'low',
        'medium',
        'high',
        'critical',
    ];

    public const STATUSES = [
        'submission',
        'pending_approval',
        'approved',
        'assigned',
        'development',
        'testing',
        'validation',
        'completed',
        'post_implementation_review',
        'rejected',
        'cancelled',
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

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
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
