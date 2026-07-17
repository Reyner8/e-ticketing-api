<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\AssignedTeam;
use App\Enums\FeatureRequestStatus;
use App\Enums\Priorities;
use App\Enums\RequestType;
use App\Enums\TargetApplication;
use App\Observers\FeatureRequestObserver;
use App\Traits\HasActivityLog;
use App\Traits\HasApproval;
use App\Traits\HasAssignment;
use App\Traits\HasAttachments;
use App\Traits\HasComments;
use App\Traits\HasStatusHistory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'id',
    'title',
    'description',
    'request_type',
    'target_application',
    'priority',
    'status',
    'progress',
    'reporter_id',
    'assigned_to_id',
    'assigned_team',
    'date_submitted',
    'approval_date',
    'assignment_date',
    'start_date',
    'due_date',
    'completion_date',
    'review_date',
    'estimated_effort',
    'actual_effort',
    'sla_time_elapsed',
    'sla_time_remaining',
    'sla_breached',
    'approval_status',
    'approved_by',
    'rejection_reason',
    'roi_impact',
    'quality_impact',
    'post_implementation_notes',
    'source_ticket_id',
    'is_direct_input',
])]

#[ObservedBy([FeatureRequestObserver::class])]

class FeatureRequest extends Model
{
    use HasComments, HasAttachments, HasStatusHistory, HasActivityLog, HasAssignment, HasApproval;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'status' => FeatureRequestStatus::class,
        'assigned_team' => AssignedTeam::class,
        'priority' => Priorities::class,
        'request_type' => RequestType::class,
        'target_application' => TargetApplication::class,
        'approval_status' => ApprovalStatus::class,
        'approval_date' => 'datetime',
        'assignment_date' => 'datetime',
        'date_reported' => 'datetime'
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

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class, 'feature_request_id');
    }

    public function completedMilestone(): HasMany
    {
        return $this->hasMany(Milestone::class, 'feature_request_id')
            ->where('is_completed', true);
    }

    public function pendingMilestone(): HasMany
    {
        return $this->hasMany(Milestone::class, 'feature_request_id')
            ->where('is_completed', false);
    }

    public function timelineEntries(): HasMany
    {
        return $this->hasMany(TimelineEntry::class, 'feature_request_id')->orderBy('phase');
    }

    public function completedTimeline(): HasMany
    {
        return $this->hasMany(TimelineEntry::class, 'feature_request_id')->where('is_completed', true);
    }

    public function pendingTimeline(): HasMany
    {
        return $this->hasMany(TimelineEntry::class, 'feature_request_id')->where('is_completed', false);
    }

    // Helpers
    public function calculateOverallProgress(): int
    {
        $milestones = $this->milestones;

        if ($milestones->isEmpty()) {
            return 0;
        }

        return (int) $milestones->avg('progress');
    }
    
    public function calculateTimelineProgress(): int
    {
        $entries = $this->timelineEntries;

        if ($entries->isEmpty()) {
            return 0;
        }

        return (int) $entries->avg('progress');
    }
}
