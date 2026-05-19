<?php

namespace App\Traits;

use App\Enums\ApprovalStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $approval_status
 */

trait HasApproval
{
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Helper
    public function isPending(): bool
    {
        return $this->approval_status === ApprovalStatus::Pending->value
        || $this->approval_status instanceof ApprovalStatus
        && $this->approval_status === ApprovalStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->approval_status === ApprovalStatus::Approved->value
        || $this->approval_status instanceof ApprovalStatus
        && $this->approval_status === ApprovalStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->approval_status === ApprovalStatus::Rejected->value
        || $this->approval_status instanceof ApprovalStatus
        && $this->approval_status === ApprovalStatus::Rejected;
    }

    public function isApprovable(): bool
    {
        return $this->isPending();
    }
}