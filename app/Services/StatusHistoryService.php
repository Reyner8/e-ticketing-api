<?php

namespace App\Services;

use App\Enums\ErrorReportStatus;
use App\Models\ErrorReport;
use App\Models\FeatureRequest;
use App\Models\StatusHistory;
use App\Models\Ticket;
use App\Services\Log\ActivityLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StatusHistoryService
{
    public function __construct(
        private readonly ActivityLogService $logService,
        private readonly NotificationService $notificationService,
    ) {}

    public function update(
        Model $resource,
        string $newStatus,
        array $extra = []
    ): StatusHistory {
        if ($resource instanceof Ticket && $resource->isConverted()) {
            throw ValidationException::withMessages([
                'status' => ['Converted tickets cannot be updated. Manage status on the linked Error Report or Feature Request.'],
            ]);
        }

        $this->guardStatusChangeByAssignee($resource);

        $previousStatus = $resource->status instanceof \BackedEnum
            ? $resource->status->value
            : (string) $resource->status;

        if ($previousStatus === $newStatus) {
            throw ValidationException::withMessages([
                'status' => ["Status is already {$newStatus}."],
            ]);
        }

        $effectiveAt = isset($extra['effective_at'])
            ? Carbon::parse($extra['effective_at'])
            : now();

        $history = DB::transaction(function () use ($resource, $previousStatus, $newStatus, $extra, $effectiveAt) {
            $updates = ['status' => $newStatus];

            if ($resource instanceof ErrorReport) {
                if ($newStatus === ErrorReportStatus::InProgress->value && ! $resource->start_date) {
                    $updates['start_date'] = $effectiveAt;
                }

                if ($newStatus === ErrorReportStatus::Completed->value) {
                    $updates['completion_date'] = $effectiveAt;
                }
            }

            $resource->update($updates);

            if ($resource instanceof FeatureRequest) {
                $resource->syncProgressFromMilestones($newStatus);
            }

            $history = $resource->statusHistories()->create([
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'changed_by' => Auth::id(),
                'changed_at' => now(),
                'effective_at' => $effectiveAt,
                'reason' => $extra['reason'] ?? null,
                'notes' => $extra['notes'] ?? null,
            ]);

            $this->logService->logStatusChanged(
                loggable: $resource,
                previousStatus: $previousStatus,
                newStatus: $newStatus
            );

            return $history;
        });

        if ($resource instanceof Ticket) {
            $updateDetails = "Status changed from '{$previousStatus}' to '{$newStatus}'.";

            $this->notificationService->notifyTicketUpdated(
                userId: $resource->reporter_id,
                ticket: $resource,
                updateDetails: $updateDetails
            );

            if ($resource->assigned_to_id && $resource->assigned_to_id !== Auth::id()) {
                $this->notificationService->notifyTicketUpdated(
                    userId: $resource->assigned_to_id,
                    ticket: $resource,
                    updateDetails: $updateDetails
                );
            }
        }

        return $history;
    }

    public function recordStatusChange(
        Model $resource,
        string $previousStatus,
        string $newStatus,
        array $extra = [],
    ): ?StatusHistory {
        if ($previousStatus === $newStatus) {
            return null;
        }

        return $this->record($resource, $previousStatus, $newStatus, $extra);
    }

    public function recordInitialStatus(Model $resource, string $status, array $extra = []): StatusHistory
    {
        return $resource->statusHistories()->create([
            'previous_status' => $status,
            'new_status' => $status,
            'changed_by' => Auth::id(),
            'changed_at' => now(),
            'effective_at' => isset($extra['effective_at'])
                ? Carbon::parse($extra['effective_at'])
                : now(),
            'reason' => $extra['reason'] ?? 'Initial status',
            'notes' => $extra['notes'] ?? null,
        ]);
    }

    private function guardStatusChangeByAssignee(Model $resource): void
    {
        $user = Auth::user();
        $role = $user->role->value;

        if (in_array($role, ['admin', 'team_lead'], true)) {
            return;
        }

        if ($role !== 'it_staff') {
            throw ValidationException::withMessages([
                'status' => ['You are not allowed to change the status of this resource.'],
            ]);
        }

        if (! $resource->assigned_to_id) {
            throw ValidationException::withMessages([
                'status' => ['Claim or get assigned to this resource before changing its status.'],
            ]);
        }

        if ((int) $resource->assigned_to_id !== $user->id) {
            throw ValidationException::withMessages([
                'status' => ['Only the assigned IT staff member can change the status.'],
            ]);
        }
    }

    public function record(
        Model $resource,
        string $previousStatus,
        string $newStatus,
        array $extra = [],
    ): StatusHistory {
        $effectiveAt = isset($extra['effective_at'])
            ? Carbon::parse($extra['effective_at'])
            : now();

        return $resource->statusHistories()->create([
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'changed_by' => Auth::id(),
            'changed_at' => now(),
            'effective_at' => $effectiveAt,
            'reason' => $extra['reason'] ?? null,
            'notes' => $extra['notes'] ?? null,
        ]);
    }

    public function getByResource(Model $resource, int $perPage = 15): LengthAwarePaginator
    {
        return $resource->statusHistories()
            ->with('changer:id,name,username')
            ->orderBy('effective_at')
            ->orderBy('id')
            ->paginate(min($perPage, 50));
    }
}
