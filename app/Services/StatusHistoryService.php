<?php

namespace App\Services;

use App\Models\StatusHistory;
use App\Services\Log\ActivityLogService;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StatusHistoryService
{
    protected ActivityLogService $logService;

    public function __construct(ActivityLogService $logService)
    {
        $this->logService = $logService;
    }   
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

        return DB::transaction(function () use ($resource, $previousStatus, $newStatus, $extra) {
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
