<?php

namespace App\Http\Controllers\Api\v1\Comment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Ticket;
use App\Services\Comment\MentionService;
use App\Traits\HandleComments;

class TicketCommentController extends Controller
{
    use HandleComments;

    public function __construct(
        protected MentionService $mentionService
    ) {}

    protected function getMentionService(): MentionService
    {
        return $this->mentionService;
    }

    public function index(Ticket $ticket)
    {
        return $this->indexComment($ticket);
    }

    public function store(StoreCommentRequest $request, Ticket $ticket)
    {
        return $this->storeComment($request, $ticket);
    }

    public function destroy(Ticket $ticket, Comment $comment)
    {
        return $this->destroyComment($ticket, $comment);
    }
}
