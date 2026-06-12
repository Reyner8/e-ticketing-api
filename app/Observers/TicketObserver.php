<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Services\Log\ActivityLogService;

class TicketObserver
{
    public function __construct(
        private readonly ActivityLogService $logService
    ) {}

    /**
     * Handle the Ticket "created" event.
     */
    public function created(Ticket $ticket): void
    {
        $this->logService->logCreated($ticket, [
            'title' => $ticket->title,
            'category' => $ticket->category,
            'priority' => $ticket->priority,
            'status' => $ticket->status,
        ]);
    }

    /**
     * Handle the Ticket "updated" event.
     */
    public function updated(Ticket $ticket): void
    {
        $changes = [];

        foreach ($ticket->getChanges() as $field => $newValue) {

            if (in_array($field, [
                'updated_at',
                'status',
                'assigned_to_id'
            ])) {
                continue;
            }

            $changes[$field] = [
                'old' => $ticket->getOriginal($field),
                'new' => $newValue,
            ];
        }

        if (!empty($changes)) {
            $this->logService->logUpdated($ticket, $changes);
        }
    }

    /**
     * Handle the Ticket "deleted" event.
     */
    public function deleted(Ticket $ticket): void
    {
        //
    }

    /**
     * Handle the Ticket "restored" event.
     */
    public function restored(Ticket $ticket): void
    {
        //
    }

    /**
     * Handle the Ticket "force deleted" event.
     */
    public function forceDeleted(Ticket $ticket): void
    {
        //
    }
}
