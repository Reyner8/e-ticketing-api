<?php

namespace App\Observers;

use App\Models\FeatureRequest;
use App\Services\Log\ActivityLogService;

class FeatureRequestObserver
{
    public function __construct(
        private readonly ActivityLogService $logService
    ) {}
    /**
     * Handle the FeatureRequest "created" event.
     */
    public function created(FeatureRequest $featureRequest): void
    {
        $this->logService->logCreated($featureRequest, [
            'title' => $featureRequest->title,
            'priority' => $featureRequest->priority,
            'status' => $featureRequest->status
        ]);
    }

    /**
     * Handle the FeatureRequest "updated" event.
     */
    public function updated(FeatureRequest $feature): void
    {
        $changes = [];

        foreach ($feature->getChanges() as $field => $newValue) {

            if (in_array($field, [
                'updated_at',
                'status',
                'assigned_to_id'
            ])) {
                continue;
            }

            $changes[$field] = [
                'old' => $feature->getOriginal($field),
                'new' => $newValue,
            ];
        }

        if (!empty($changes)) {
            $this->logService->logUpdated($feature, $changes);
        }
    }

    /**
     * Handle the FeatureRequest "deleted" event.
     */
    public function deleted(FeatureRequest $featureRequest): void
    {
        //
    }

    /**
     * Handle the FeatureRequest "restored" event.
     */
    public function restored(FeatureRequest $featureRequest): void
    {
        //
    }

    /**
     * Handle the FeatureRequest "force deleted" event.
     */
    public function forceDeleted(FeatureRequest $featureRequest): void
    {
        //
    }
}
