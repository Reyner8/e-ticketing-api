<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\AssignedTeam;
use App\Enums\ErrorCategory;
use App\Enums\ErrorReportStatus;
use App\Enums\Priorities;
use App\Observers\ErrorReportObserver;
use App\Policies\ErrorReportPolicy;
use App\Traits\HasActivityLog;
use App\Traits\HasApproval;
use App\Traits\HasAssignment;
use App\Traits\HasAttachments;
use App\Traits\HasComments;
use App\Traits\HasStatusHistory;
use App\Traits\HasTags;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Override;

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
    'assignment_date',
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
    'approval_status',
    'approved_by',
    'approval_date',
    'rejection_reason',
])]

#[ObservedBy([ErrorReportObserver::class])]
#[UsePolicy(ErrorReportPolicy::class)]

class ErrorReport extends Model
{
    use HasComments, HasAttachments, HasStatusHistory, HasActivityLog, HasAssignment, HasApproval, HasTags;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'status' => ErrorReportStatus::class,
        'assigned_team' => AssignedTeam::class,
        'priority' => Priorities::class,
        'category' => ErrorCategory::class,
        'assignment_date' => 'datetime',
        'date_reported' => 'datetime',
        'start_date' => 'datetime',
        'due_date' => 'datetime',
        'completion_date' => 'datetime',
        'estimated_effort' => 'decimal:2',
        'actual_effort' => 'decimal:2',
        'sla_time_elapsed' => 'decimal:2',
        'sla_time_remaining' => 'decimal:2',
        'approval_status' => ApprovalStatus::class,
        'approval_date' => 'datetime',
    ];

    // Relations
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function sourceTicket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'source_ticket_id');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    // Helpers
    public function isAssignedToUser(): bool
    {
        return ! is_null($this->assigned_to_id);
    }

    public function isAssignedToTeam(): bool
    {
        return ! is_null($this->assigned_team);
    }

    public function isAssignable(): bool
    {
        $currentStatus = $this->status->value;

        return in_array($currentStatus, ErrorReportStatus::assignableStatuses());
    }

    public function isCompleted(): bool
    {
        return $this->status === ErrorReportStatus::Completed;
    }

    public function isFromTicket(): bool
    {
        return ! is_null($this->source_ticket_id);
    }

    public function isApprovable(): bool
    {
        if ($this->isPending()) {
            return true;
        }

        return $this->status === ErrorReportStatus::PendingApproval
            && ($this->approval_status === null || $this->approval_status === ApprovalStatus::Pending);
    }

    // Scopes
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeSlaBreached(Builder $query): Builder
    {
        return $query->where('sla_breached', true);
    }

    public function scopeDirectInput(Builder $query): Builder
    {
        return $query->where('is_direct_input', true);
    }

    public function scopeFromTicket(Builder $query): Builder
    {
        return $query->whereNotNull('source_ticket_id');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('due_date')
            ->where('due_date', '<=', now())
            ->whereNotIn('status', ['completed']);
    }
}
