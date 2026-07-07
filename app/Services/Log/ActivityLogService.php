<?php

namespace App\Services\Log;

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    public function log(
        Model $loggable,
        ActivityAction $action,
        string $description,
        int $performedBy,
        array $details,
        ?int $targetUserId = null,

    ): ActivityLog {
        return $loggable->activityLogs()->create([
            'action' => $action->value,
            'description' => $description,
            'performed_by' => $performedBy,
            'details' => empty($details) ? null : $details,
            'target_user_id' => $targetUserId,
        ]);
    }

    //* Shorthand
    public function logCreated(Model $loggable, array $details = []): ActivityLog
    {
        return $this->log(
            loggable: $loggable,
            action: ActivityAction::Created,
            description: class_basename($loggable) . ' was created.',
            performedBy: Auth::id(),
            details: $details,
        );
    }

    public function logUpdated(Model $loggable, array $changedFields = []): ActivityLog
    {
        return $this->log(
            loggable: $loggable,
            action: ActivityAction::Updated,
            description: class_basename($loggable) . ' was updated.',
            performedBy: Auth::id(),
            details: ['changed_fields' => $changedFields],
        );
    }

    public function logAssigned(Model $loggable, string $assigneeName): ActivityLog
    {
        return $this->log(
            loggable: $loggable,
            action: ActivityAction::Assigned,
            description: class_basename($loggable) . " was assigned to {$assigneeName}.",
            performedBy: Auth::id(),
            details: ['assignee_name' => $assigneeName],
        );
    }

    public function logCommented(Model $loggable, string $preview): ActivityLog
    {
        return $this->log(
            loggable: $loggable,
            action: ActivityAction::Commented,
            description: 'A comment was added.',
            performedBy: Auth::id(),
            details: ['preview' => $preview],
        );
    }

    public function logStatusChanged(Model $loggable, string $previousStatus, string $newStatus): ActivityLog
    {
        return $this->log(
            loggable: $loggable,
            action: ActivityAction::StatusChanged,
            description: "Status changed from '{$previousStatus}' to '{$newStatus}'.",
            performedBy: Auth::id(),
            details: [
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
            ],
        );
    }

    public function logAttachmentAdded(Model $loggable, string $fileName): ActivityLog
    {
        return $this->log(
            loggable: $loggable,
            action: ActivityAction::AttachmentAdded,
            description: "Attachment {$fileName} was added.",
            performedBy: Auth::id(),
            details: ['file_name' => $fileName],
        );
    }

    public function logMentioned(Model $loggable, int $targetUserId, string $targetUserName): ActivityLog
    {
        return $this->log(
            loggable: $loggable,
            action: ActivityAction::MentionAdded,
            description: "{$targetUserName} was mentioned.",
            performedBy: Auth::id(),
            details: ['mentioned_user' => $targetUserName],
            targetUserId: $targetUserId,
        );
    }

    public function logMilestoneReached(Model $loggable, string $milestone, array $details = []): ActivityLog
    {
        return $this->log(
            loggable: $loggable,
            action: ActivityAction::MilestoneReached,
            description: class_basename($loggable) . " reached milestone: {$milestone}.",
            performedBy: Auth::id(),
            details: [
                'milestone' => $milestone,
                ...$details,
            ]
        );
    }

    public function logConverted(Model $loggable, string $fromType, string $toType): ActivityLog
    {
        return $this->log(
            loggable: $loggable,
            action: ActivityAction::Converted,
            description: "{$fromType} was converted to {$toType}.",
            performedBy: Auth::id(),
            details: [
                'from_type' => $fromType,
                'to_type' => $toType,
            ]
        );
    }

    //* Query
    public function getByResource(
        Model $loggable,
        int $perPage = 15,
        ?string $action = null
    ): LengthAwarePaginator {
        return $loggable->activityLogs()
        ->with([
            'performer:id,name,username',
            'targetUser:id,name,username',
        ])
        ->when($action, fn ($q) => $q->where('action', $action))
        ->latest('performed_at')
        ->paginate(min($perPage, 50));
    }

    public function getRecent(int $perPage = 20, ?string $action = null): LengthAwarePaginator
    {
        return ActivityLog::query()
            ->with([
                'performer:id,name,username',
                'targetUser:id,name,username',
            ])
            ->when($action, fn ($q) => $q->where('action', $action))
            ->latest('performed_at')
            ->paginate(min($perPage, 50));
    }
}
