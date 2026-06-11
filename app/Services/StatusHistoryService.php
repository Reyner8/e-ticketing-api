<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\TicketStatus;
use App\Models\StatusHistory;
use App\Models\Ticket;
use App\Services\Log\ActivityLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StatusHistoryService
{
    public function __construct(
        private readonly ActivityLogService $logService,
        private readonly NotificationService $notificationService,
        private readonly TicketWatcherService $watcherService,
    ) {}
    /**
     * @param Model $resource           Resource whose status has changed
     * @param string $newStatus         New Status
     * @param array $extra              Additional Attribute: reason, notes
     * @return StatusHistory
     */
    public function update(
        Model $resource,
        string $newStatus,
        array $extra = []
    ): StatusHistory {
        $previousStatus = $resource->status instanceof \BackedEnum
            ? $resource->status->value
            : (string) $resource->status;

        if ($previousStatus === $newStatus) {
            throw ValidationException::withMessages([
                'status' => ["Status is already {$newStatus}."]
            ]);
        }

        $history = DB::transaction(function () use ($resource, $previousStatus, $newStatus, $extra) {
            $resource->update(['status' => $newStatus]);

            $history = $resource->statusHistories()->create([
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'changed_by' => Auth::id(),
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

        //* notification
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

            //* watcher notification
            $this->watcherService->notifyWatchers(
                ticket: $resource,
                event: ActivityAction::StatusChanged->value,
                details: [
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus
                ]
            );
        }

        return $history;
    }

    public function record(
        Model $resource,
        string $previousStatus,
        string $newStatus,
        array $extra = [],
    ): StatusHistory {
        return $resource->statusHistories()->create([
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'changed_by' => (string) Auth::id(),
            'reason' => $extra['reason'] ?? null,
            'notes' => $extra['notes'] ?? null,
        ]);
    }

    public function getByResource(Model $resource, int $perPage = 15): LengthAwarePaginator
    {
        return $resource->statusHistories()
            ->with('changer:id,name,username')
            ->latest('changed_at')
            ->paginate(min($perPage, 50));
    }
}
