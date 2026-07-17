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
    'due_date',
    'sla_breached',
    'approval_status',
    'approved_by',
    'rejection_reason',
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
        'due_date' => 'datetime',
    ];

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

    /**
     * Progress = rata-rata progress semua milestone.
     * Tanpa milestone → 0.
     */
    public function calculateOverallProgress(): int
    {
        $milestones = $this->milestones()->get();

        if ($milestones->isEmpty()) {
            return 0;
        }

        return (int) round($milestones->avg('progress'));
    }

    /**
     * Hitung ulang progress feature dari milestone lalu simpan.
     * Status Completed / Post Implementation Review dianggap 100% penuh.
     */
    public function syncProgressFromMilestones(?string $statusValue = null): void
    {
        $status = $statusValue ?? ($this->status instanceof FeatureRequestStatus
            ? $this->status->value
            : (string) $this->status);

        $isDone = in_array($status, [
            FeatureRequestStatus::Completed->value,
            FeatureRequestStatus::PostImplementationReview->value,
        ], true);

        $progress = $isDone ? 100 : $this->calculateOverallProgress();

        $this->update(['progress' => $progress]);
    }
}
