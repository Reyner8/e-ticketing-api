<?php

namespace App\Http\Controllers\Api\v1\Comment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Models\Comment;
use App\Models\ErrorReport;
use App\Services\Comment\MentionService;
use App\Traits\HandleComments;

class ErrorReportCommentController extends Controller
{
    use HandleComments;

    public function __construct(protected MentionService $mentionService) {}

    protected function getMentionService(): MentionService
    {
        return $this->getMentionService();
    }

    public function index(ErrorReport $error)
    {
        return $this->indexComment($error);
    }

    public function store(StoreCommentRequest $request, ErrorReport $error)
    {
        return $this->storeComment($request, $error);
    }

    public function destroy(ErrorReport $error, Comment $comment)
    {
        return $this->destroyComment($error, $comment);
    }
}
