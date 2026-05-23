<?php

namespace App\Observers;

use App\Models\ErrorReport;
use App\Services\Log\ActivityLogService;

class ErrorReportObserver
{
    public function __construct(
        private readonly ActivityLogService $logService
    ) {}
    /**
     * Handle the ErrorReport "created" event.
     */
    public function created(ErrorReport $errorReport): void
    {
        $this->logService->logCreated($errorReport, [
            'title' => $errorReport->title,
            'priority' => $errorReport->priority,
            'status' => $errorReport->status
        ]);
    }

    /**
     * Handle the ErrorReport "updated" event.
     */
    public function updated(ErrorReport $error): void
    {
        $changes = [];

        foreach ($error->getChanges() as $field => $newValue) {

            if (in_array($field, [
                'updated_at',
                'status',
                'assigned_to_id'
            ])) {
                continue;
            }

            $changes[$field] = [
                'old' => $error->getOriginal($field),
                'new' => $newValue,
            ];
        }

        if (!empty($changes)) {
            $this->logService->logUpdated($error, $changes);
        }
    }

    /**
     * Handle the ErrorReport "deleted" event.
     */
    public function deleted(ErrorReport $errorReport): void
    {
        //
    }

    /**
     * Handle the ErrorReport "restored" event.
     */
    public function restored(ErrorReport $errorReport): void
    {
        //
    }

    /**
     * Handle the ErrorReport "force deleted" event.
     */
    public function forceDeleted(ErrorReport $errorReport): void
    {
        //
    }
}
