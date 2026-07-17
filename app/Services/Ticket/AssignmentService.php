<?php

namespace App\Services\Ticket;

use App\Enums\ActivityAction;
use App\Services\Log\ActivityLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Enums\AssignedTeam;
use App\Enums\ErrorReportStatus;
use App\Enums\FeatureRequestStatus;
use App\Enums\TicketStatus;
use App\Models\ErrorReport;
use App\Models\FeatureRequest;
use App\Models\Ticket;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\StatusHistoryService;
use Illuminate\Validation\ValidationException;
use BackedEnum;
use App\Enums\Priorities;
use Illuminate\Support\Carbon;

class AssignmentService
{
    public function __construct(
        private readonly ActivityLogService $logService,
        private readonly NotificationService $notificationService,
        private readonly StatusHistoryService $statusHistoryService,
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
            $newStatus = $this->resolveStatusAfterAssignment($resource);
            $updates = [
                'assigned_to_id' => $user->id,
                'status' => $newStatus,
            ];

            if (! $resource instanceof FeatureRequest) {
                $updates['assignment_date'] = now();
            }

            if ($resource instanceof FeatureRequest) {
                $updates['due_date'] = $this->calculateDueDate($resource->priority->value, now());
            } elseif ($resource instanceof Ticket || $resource instanceof ErrorReport) {
                $updates['due_date'] = $this->calculateDueDate($resource->priority->value, now());
            }

            $resource->update($updates);

            $description = $previousAssignee
                ? "Reassign from '{$previousAssignee}' to '{$user->name}'."
                : "Assign to '{$user->name}'.";

            // log assignment 
            $this->logService->log(
                loggable: $resource,
                action: ActivityAction::Assigned,
                description: $description,
                performedBy: Auth::id(),
                details: array_filter([
                    'assign_to' => $user->name,
                    'previous_assignee' => $previousAssignee,
                    'assignment_date' => now()->format('Y-m-d H:i:s'),
                ])
            );

            // log status if status changed
            if ($previousStatus !== $resource->status->value) {
                $this->logService->logStatusChanged(
                    loggable: $resource,
                    previousStatus: $previousStatus,
                    newStatus: $resource->status->value
                );

                if ($resource instanceof FeatureRequest) {
                    $this->statusHistoryService->recordStatusChange(
                        $resource,
                        $previousStatus,
                        $resource->status->value,
                        ['reason' => 'Assigned to user']
                    );
                }
            }

            // notification
            if ($resource instanceof Ticket) {
                $this->notificationService->notifyTicketAssigned(
                    userId: $user->id,
                    ticket: $resource
                );
            }
        });

        return $resource->load('assignedUser');
    }

    /**
     * IT staff claims an unassigned resource for themselves.
     */
    public function claim(Model $resource): Model
    {
        $user = Auth::user();

        if ($user->role->value !== 'it_staff') {
            throw ValidationException::withMessages([
                'claim' => ['Only IT staff can claim resources.']
            ]);
        }

        if ($resource->assigned_to_id) {
            if ((int) $resource->assigned_to_id === $user->id) {
                throw ValidationException::withMessages([
                    'claim' => ['You have already claimed this resource.']
                ]);
            }

            throw ValidationException::withMessages([
                'claim' => ['This resource is already assigned to another user.']
            ]);
        }

        return $this->assignToUser($resource, $user->id);
    }

    public function assignToTeam(Model $resource, string $team): Model
    {
        $this->guardNotAssignable($resource);

        $assignedTeam = AssignedTeam::from($team);

        $currentTeam = $resource->assigned_team instanceof AssignedTeam
            ? $resource->assigned_team->value
            : $resource->assigned_team;

        if ($currentTeam === $assignedTeam->value) {
            throw ValidationException::withMessages([
                'team' => [
                    "Resource is already assigned to '{$assignedTeam->label()}'"
                ]
            ]);
        }


        $previousTeamLabel = $resource->assigned_team instanceof AssignedTeam
            ? $resource->assigned_team->label()
            : $resource->assigned_team;

        $previousStatus = $resource->status instanceof BackedEnum
            ? $resource->status->value
            : $resource->status;

        DB::transaction(function () use ($resource, $assignedTeam, $previousTeamLabel, $previousStatus) {
            $newStatus = $this->resolveStatusAfterAssignment($resource);
            $updates = [
                'assigned_team' => $assignedTeam->value,
                'status' => $newStatus,
            ];

            if (! $resource instanceof FeatureRequest) {
                $updates['assignment_date'] = now();
            }

            if ($resource instanceof FeatureRequest) {
                $updates['due_date'] = $this->calculateDueDate($resource->priority->value, now());
            } elseif ($resource instanceof Ticket || $resource instanceof ErrorReport) {
                $updates['due_date'] = $this->calculateDueDate($resource->priority->value, now());
            }

            $resource->update($updates);

            $description = $previousTeamLabel
                ? "Reassign from '{$previousTeamLabel}' to '{$assignedTeam->label()}'."
                : "Assigned to team '{$assignedTeam->value}'.";

            // log assignment
            $this->logService->log(
                loggable: $resource,
                action: ActivityAction::Assigned,
                description: $description,
                performedBy: Auth::id(),
                details: array_filter([
                    'assigned_team' => $assignedTeam->value,
                    'previous_team' => $previousTeamLabel,
                    'assignment_date' => now()->format('Y-m-d H:i:s'),
                ])
            );

            // log status if status changed
            if ($previousStatus !== $resource->status->value) {
                $this->logService->logStatusChanged(
                    loggable: $resource,
                    previousStatus: $previousStatus,
                    newStatus: $resource->status->value
                );

                if ($resource instanceof FeatureRequest) {
                    $this->statusHistoryService->recordStatusChange(
                        $resource,
                        $previousStatus,
                        $resource->status->value,
                        ['reason' => 'Assigned to user']
                    );
                }
            }

            // notification
            if ($resource instanceof Ticket) {
                $this->notifyTeamMember($resource, $assignedTeam->value);
            }
        });

        return $resource->load('assignedUser');
    }

    // Helpers
    public function unassignUser(Model $resource): Model
    {
        if (! $resource->isAssignedToUser()) {
            throw ValidationException::withMessages([
                'user_id' => ['Resource is not assigned to any user.']
            ]);
        }

        $previousAssignee = $resource->assignedUser?->name;

        DB::transaction(function () use ($resource, $previousAssignee) {
            $updates = ['assigned_to_id' => null];
            if (! $resource instanceof FeatureRequest) {
                $updates['assignment_date'] = null;
            }
            $resource->update($updates);

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
            throw ValidationException::withMessages([
                'team' => ['Resource is not assigned to any team.']
            ]);
        }

        $previousTeam = $resource->assigned_team instanceof AssignedTeam
            ? $resource->assigned_team->label()
            : $resource->assigned_team;

        DB::transaction(function () use ($resource, $previousTeam) {
            $updates = ['assigned_team' => null];
            if (! $resource instanceof FeatureRequest) {
                $updates['assignment_date'] = null;
            }
            $resource->update($updates);

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

    // Private
    private function guardNotAssignable(Model $resource): void
    {
        $currentStatus = $resource->status instanceof BackedEnum
            ? $resource->status->value
            : $resource->status;

        $assignableStatuses = array_map(
            fn ($status) => $status instanceof BackedEnum ? $status->value : $status,
            $this->resolveAssignableStatus($resource)
        );

        if (! in_array($currentStatus, $assignableStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => [
                    "Resource with status '{$currentStatus}' cannot be assigned."
                ]
            ]);
        }
    }

    private function resolveAssignableStatus(Model $resource): array
    {
        return match (true) {
            $resource instanceof Ticket => TicketStatus::assignableStatuses(),
            $resource instanceof FeatureRequest => FeatureRequestStatus::assignableStatuses(),
            $resource instanceof ErrorReport => ErrorReportStatus::assignableStatuses(),
            default => [],
        };
    }

    private function resolveStatusAfterAssignment(Model $resource): string
    {
        $currentStatus = $resource->status instanceof BackedEnum
            ? $resource->status->value
            : $resource->status;

        $preAssignedStatus = [
            TicketStatus::Draft->value,
            TicketStatus::PendingApproval->value,
            ErrorReportStatus::PendingApproval->value,
            FeatureRequestStatus::Submission->value,
            FeatureRequestStatus::PendingApproval->value,
            FeatureRequestStatus::Approved->value,
        ];

        return in_array($currentStatus, $preAssignedStatus)
            ? $this->resolveAssignedStatus($resource)
            : $currentStatus;
    }

    private function resolveAssignedStatus(Model $resource): string
    {
        return match (true) {
            $resource instanceof Ticket => TicketStatus::Assigned->value,
            $resource instanceof FeatureRequest => FeatureRequestStatus::Assigned->value,
            $resource instanceof ErrorReport => ErrorReportStatus::Assigned->value,
            default => 'assigned',
        };
    }

    private function notifyTeamMember(Ticket $ticket, string $team): void
    {
        $teamMembers = User::where('role', 'it_staff')
        ->where('is_active', true)
        ->get();

        foreach ($teamMembers as $member) {
            $this->notificationService->notifyTicketAssigned(
                userId: $member->id,
                ticket: $ticket
            );
        }
    }

    private function calculateDueDate(string $priority, Carbon $assignmentDate): Carbon
    {
        $priorityEnum = Priorities::tryFrom($priority);
        $hours = $priorityEnum ? $priorityEnum->slaHours() : 48;

        return $assignmentDate->copy()->addHours($hours);
    }
}
