<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\AssignedTeam;
use App\Enums\ConversionTypes;
use App\Enums\Priorities;
use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use App\Observers\TicketObserver;
use App\Policies\TicketPolicy;
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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    'assignment_date',
    'date_reported',
    'due_date',
    'resolved_date',
    'closed_date',
    'sla_breached',
    'response_time',
    'resolution_time',
    'estimated_effort',
    'actual_effort',
    'parent_ticket_id',
    'converted_to_type',
    'converted_to_id',
    'converted_at',
    'converted_by',
    'conversion_reason',
    'is_public_submission',
    'submitter_name',
    'submitter_email',
    'submitter_phone',
    'submitter_unit',
    'approval_status',
    'approved_by',
    'approval_date',
    'rejection_reason',
])]

#[ObservedBy([TicketObserver::class])]
#[UsePolicy(TicketPolicy::class)]

class Ticket extends Model
{
    use HasComments, HasAttachments, HasStatusHistory, HasActivityLog, HasAssignment, HasApproval, HasTags;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'status' => TicketStatus::class,
        'assigned_team' => AssignedTeam::class,
        'priority' => Priorities::class,
        'category' => TicketCategory::class,
        'converted_to_type' => ConversionTypes::class,
        'assignment_date' => 'datetime',
        'date_reported' => 'datetime',
        'due_date' => 'datetime',
        'resolved_date' => 'datetime',
        'closed_date' => 'datetime',
        'converted_at' => 'datetime',
        'sla_breached' => 'boolean',
        'is_public_submission' => 'boolean',
        'approval_status' => ApprovalStatus::class,
        'approval_date' => 'datetime',
        'response_time' => 'decimal:2',
        'resolution_time' => 'decimal:2',
        'estimated_effort' => 'decimal:2',
        'actual_effort' => 'decimal:2',
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

    public function converter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by');
    }

    public function parentTicket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'parent_ticket_id', 'id');
    }

    public function childTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'parent_ticket_id', 'id');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function watchers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ticket_watchers', 'ticket_id', 'user_id')
            ->using(TicketWatcher::class)
            ->withPivot('created_at');
    }

    public function conversionHistories(): HasMany
    {
        return $this->hasMany(ConversionHistory::class, 'source_ticket_id');
    }

    public function mergedTickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'merged_tickets', 'parent_ticket_id', 'merged_ticket_id')
            ->using(MergedTicket::class)
            ->withPivot(['merged_by', 'merged_at']);
    }

    public function mergedInto(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'merged_tickets', 'merged_ticket_id', 'parent_ticket_id')
            ->using(MergedTicket::class)
            ->withPivot(['merged_by', 'merged_at']);
    }

    // Helpers
    public function isConverted(): bool
    {
        return ! is_null($this->converted_to_id);
    }

    public function canBeConverted(): bool
    {
        $allowedStatuses = [
            TicketStatus::Draft->value,
            TicketStatus::PendingApproval->value,
            TicketStatus::Assigned->value,
            TicketStatus::InProgress->value,
            TicketStatus::WaitingForUser->value
        ];

        $currentStatus = $this->status->value;

        return in_array($currentStatus, $allowedStatuses);
    }

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

        return in_array($currentStatus, TicketStatus::assignableStatuses());
    }

    public function isApprovable(): bool
    {
        if ($this->isPending()) {
            return true;
        }

        return $this->status === TicketStatus::PendingApproval
            && ($this->approval_status === null || $this->approval_status === ApprovalStatus::Pending);
    }

    public function convertedUrl()
    {
        if (!$this->isConverted()) return null;

        return match ($this->converted_to_type) {
            ConversionTypes::ErrorReport => route('error-report.show', $this->converted_to_id),
            ConversionTypes::FeatureRequest => route('feature-request.show', $this->converted_to_id),
            default => null
        };
    }

    public function isWatchedBy(int $userId): bool
    {
        return $this->watchers()->where('users.id', $userId)->exists();
    }

    public function watchersCount(): int
    {
        return $this->watchers()->count();
    }

    public function isMerged(): bool
    {
        return $this->mergedInto()->exists();
    }

    public function hasMergedTickets(): bool
    {
        return $this->mergedTickets()->exists();
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

    public function scopeBySlaBreached(Builder $query): Builder
    {
        return $query->where('sla_breached', true);
    }

    public function scopeByOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->whereNotIn(['closed', 'resolved', 'converted']);
    }
}
