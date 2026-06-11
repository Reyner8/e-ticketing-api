<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\AssignedTeam;
use App\Enums\ConversionTypes;
use App\Enums\Priorities;
use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use App\Observers\TicketObserver;
use App\Traits\HasActivityLog;
use App\Traits\HasApproval;
use App\Traits\HasAssignment;
use App\Traits\HasAttachments;
use App\Traits\HasComments;
use App\Traits\HasStatusHistory;
use App\Traits\HasTags;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
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
    'approval_status',
    'approved_by',
    'approval_date',
    'rejection_reason'
])]

#[ObservedBy([TicketObserver::class])]

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
        'approval_status' => ApprovalStatus::class,
        'approval_date' => 'datetime',
        'assignment_date' => 'datetime',
        'date_reported' => 'datetime',
        'due_date' => 'datetime',
        'resolved_date' => 'datetime',
        'closed_date' => 'datetime',
        'converted_at' => 'datetime',
        'sla_breached' => 'boolean',
        'response_time' => 'integer',
        'resolution_time' => 'integer',
        'estimated_effort' => 'integer',
        'actual_effort' => 'integer',
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

    public function convertedBy()
    {
        return $this->belongsTo(User::class, 'converted_by');
    }

    public function parentTicket()
    {
        return $this->belongsTo(Ticket::class, 'parent_ticket_id', 'id');
    }

    public function childTickets()
    {
        return $this->hasMany(Ticket::class, 'parent_ticket_id', 'id');
    }

    public function featureRequest()
    {
        return $this->hasOne(FeatureRequest::class, 'source_ticket_id');
    }

    public function errorReport()
    {
        return $this->hasOne(ErrorReport::class, 'source_ticket_id');
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
        return $this->status === TicketStatus::Converted;
    }

    public function isApproved(): bool
    {
        return $this->approval_status === ApprovalStatus::Approved;
    }

    public function canBeConverted(): bool
    {
        return in_array(
            $this->status,
            TicketStatus::assignableStatuses(),
            true
        ) && $this->isApproved();
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
}
