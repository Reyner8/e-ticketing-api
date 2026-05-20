<?php

namespace App\Services\Ticket;

use App\Enums\ActivityAction;
use App\Services\Log\ActivityLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Enums\AssignedTeam;
use App\Enums\TicketStatus;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AssignmentService
{
    public function __construct(
        private readonly ActivityLogService $logService
    ) {}

    public function assignToUser(Model $resource, int $userId): Model
    {
        $this->guardNotAssignable($resource);

        $user = User::findOrFail($userId);
        $currentUserId = $resource->assigned_to_id;

        if ($currentUserId === $user->id) {
            throw ValidationException::withMessages([
                'user_id' => [
                    "Resource is already assigned to user '{$user->name}'."
                ]
            ]);
        }
        $previousAssignee = $resource->assignedUser?->name;
        $previousStatus = $resource->status instanceof \BackedEnum
            ? $resource->status->value
            : $resource->status;

        DB::transaction(function () use ($resource, $user, $previousAssignee, $previousStatus) {
            $resource->update([
                'assigned_to_id' => $user->id,
                'assignment_date' => Carbon::now(),
                'status' => $this->resolveStatusAfterAssignment($resource),
            ]);

            $description = $previousAssignee
                ? "Reassign from '{$previousAssignee}' to '{$user->name}'."
                : "Assign to '{$user->name}'.";
            
                $this->logService->log(
                loggable: $resource,
                action: ActivityAction::Assigned,
                description: $description,
                performedBy: Auth::id(),
                details: array_filter([
                    'assign_to' => $user->name,
                    'previous_assignee' => $previousAssignee
                ])
            );

            // log status if status changed
            if ($previousStatus !== $resource->status->value) {
                $this->logService->logStatusChanged(
                    loggable: $resource,
                    previousStatus: $previousStatus,
                    newStatus: $resource->status->value
                );
            }
        });

        return $resource->load('assignedUser');
    }

    public function assignToTeam(Model $resource, string $team): Model
    {
        $assignedTeam = AssignedTeam::from($team);
        $currentTeam = $resource->assigned_team instanceof AssignedTeam
            ? $resource->assigned_team->value
            : $resource->assigned_team;

        if ($currentTeam === $assignedTeam->value) {
            throw ValidationException::withMessages([
                'team' => [
                    "Resource is already assigned to team '{$assignedTeam->label()}'"
                ]
            ]);
        }

        $this->guardNotAssignable($resource);

        $assignedTeam = AssignedTeam::from($team);
        $previousTeam = $resource->assigned_team instanceof AssignedTeam
            ? $resource->assigned_team->value
            : $resource->assigned_team;
        $previousStatus = $resource->status instanceof \BackedEnum
            ? $resource->status->value
            : $resource->status;

        DB::transaction(function () use ($resource, $assignedTeam, $previousTeam, $previousStatus) {
            $resource->update([
                'assigned_team' => $assignedTeam->value,
                'status' => $this->resolveStatusAfterAssignment($resource)
            ]);

            $description = $previousTeam
                ? "Reassign from team '{$previousTeam}' to '{$assignedTeam->label()}'."
                : "Assigned to team '{$assignedTeam->value}'.";

            $this->logService->log(
                loggable: $resource,
                action: ActivityAction::Assigned,
                description: $description,
                performedBy: Auth::id(),
                details: array_filter([
                    'assigned_team' => $assignedTeam->value,
                    'previous_team' => $previousTeam,
                ])
            );

            // log status if status changed
            if ($previousStatus !== $resource->status->value) {
                $this->logService->logStatusChanged(
                    loggable: $resource,
                    previousStatus: $previousStatus,
                    newStatus: $resource->status->value
                );
            }
        });

        return $resource->load('assignedUser');
    }

    // Helpers
    public function unassignUser(Model $resource): Model
    {
        if (! $resource->isAssignedToUser()) {
            abort(422, 'resource is not assigned to any user.');
        }

        $previousAssignee = $resource->assignedUser?->name;

        DB::transaction(function () use ($resource, $previousAssignee) {
            $resource->update(['assigned_to_id' => null]);

            $this->logService->log(
                loggable: $resource,
                action: ActivityAction::Assigned,
                description: "User assignment removed. Previously assigned to '{$previousAssignee}'.",
                performedBy: Auth::id(),
                details: array_filter([
                    'previous_assignee' => $previousAssignee,
                ])
            );
        });
        return $resource->load('assignedUser');
    }

    public function unassignTeam(Model $resource): Model
    {
        if (! $resource->isAssignedToTeam()) {
            abort(422, 'resource is not assign to any team.');
        }

        $previousTeam = $resource->assigned_team instanceof AssignedTeam
            ? $resource->assigned_team->label()
            : $resource->assigned_team;

        DB::transaction(function () use ($resource, $previousTeam) {
            $resource->update(['assigned_team' => null]);

            $this->logService->log(
                loggable: $resource,
                action: ActivityAction::Assigned,
                description: "Team assignment removed. Previously assigned to '{$previousTeam}'.",
                performedBy: Auth::id(),
                details: array_filter([
                    'previous_team' => $previousTeam
                ])
            );
        });

        return $resource->load('assignedUser');
    }

    private function guardNotAssignable(Model $resource): void
    {
        if (!$resource->isAssignable()) {
            $status = $resource->status instanceof \BackedEnum
                ? $resource->status->value
                : $resource->status;

            abort(422, "Resource with status '{$status}' cannot be assigned");
        }
    }

    private function resolveStatusAfterAssignment(Model $resource): string
    {
        $currentStatus = $resource->status instanceof \BackedEnum
            ? $resource->status->value
            : $resource->status;

        $shouldChangeToAssigned = in_array($currentStatus, [
            TicketStatus::Draft->value,
            TicketStatus::PendingApproval->value,
        ]);

        return $shouldChangeToAssigned
            ? TicketStatus::Assigned->value
            : $currentStatus;
    }
}
