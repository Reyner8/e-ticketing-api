<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\AssignedTeam;
use App\Enums\ErrorCategory;
use App\Enums\ErrorReportStatus;
use App\Enums\Priorities;
use App\Observers\ErrorReportObserver;
use App\Traits\HasActivityLog;
use App\Traits\HasApproval;
use App\Traits\HasAssignment;
use App\Traits\HasAttachments;
use App\Traits\HasComments;
use App\Traits\HasStatusHistory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
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
    'approved_by',
    'approval_status',
    'approval_date',
    'rejection_reason',
])]

#[ObservedBy([ErrorReportObserver::class])]

class ErrorReport extends Model
{
    use HasComments, HasAttachments, HasStatusHistory, HasActivityLog, HasAssignment, HasApproval;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'status' => ErrorReportStatus::class,
        'assigned_team' => AssignedTeam::class,
        'priority' => Priorities::class,
        'category' => ErrorCategory::class,
        'approval_status' => ApprovalStatus::class,
        'approval_date' => 'datetime',
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
