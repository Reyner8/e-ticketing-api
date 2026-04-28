<?php

namespace App\Services;

use App\Models\StatusHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StatusHistoryService
{
    /**
     * @param Model $resource           Resource whose status has changed
     * @param string $previousStatus    Previous Status
     * @param string $newStatus         New Status
     * @param array $extra              Additional Attribute: reason, notes
     * @return StatusHistory
     */
    public function update(
        Model $resource,
        string $newStatus,
        array $extra = []
    ): StatusHistory {
        $previousStatus = $resource->status;

        if ($previousStatus === $newStatus) {
            abort(422, "Status is already '{$newStatus}'.");
        }

        return DB::transaction(function () use ($resource, $previousStatus, $newStatus, $extra) {
            $resource->update(['status' => $newStatus]);

            return $resource->statusHistories()->create([
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'changedBy' => Auth::id(),
                'reason' => $extra['reason'] ?? null,
                'notes' => $extra['notes'] ?? null,
            ]);
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
