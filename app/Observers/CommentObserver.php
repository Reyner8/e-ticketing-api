<?php

namespace App\Observers;

use App\Models\Comment;
use App\Services\Log\ActivityLogService;

class CommentObserver
{
    public function __construct(
        private readonly ActivityLogService $logService
    ) {}
    /**
     * Handle the Comment "created" event.
     */
    public function created(Comment $comment, string $preview): void
    {
        $this->logService->logCommented($comment, $preview);
    }

    /**
     * Handle the Comment "updated" event.
     */
    public function updated(Comment $comment): void
    {
        //
    }

    /**
     * Handle the Comment "deleted" event.
     */
    public function deleted(Comment $comment): void
    {
        //
    }

    /**
     * Handle the Comment "restored" event.
     */
    public function restored(Comment $comment): void
    {
        //
    }

    /**
     * Handle the Comment "force deleted" event.
     */
    public function forceDeleted(Comment $comment): void
    {
        //
    }
}
