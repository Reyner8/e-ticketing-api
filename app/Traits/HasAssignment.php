<?php

namespace App\Traits;

use App\Enums\AssignedTeam;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

/**
 * @property int|null $assigned_to_id
 * @property int|null $assigned_team
 * @property string $status
 */
trait HasAssignment
{
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
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

    public function isAssigned(): bool
    {
        return $this->isAssignedToUser() || $this->isAssignedToTeam();
    }

    public function isAssignable(): bool
    {
        return in_array($this->status, TicketStatus::assignableStatuses()); 
    }

    public function getAssignedTeamLabelAttribute(): ?string
    {
        if (is_null($this->assigned_team)) {
            return null;
        }

        return AssignedTeam::tryFrom($this->assigned_team)?->label();
    }
}
