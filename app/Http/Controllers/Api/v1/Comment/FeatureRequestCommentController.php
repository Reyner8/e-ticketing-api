<?php

namespace App\Http\Controllers\Api\v1\Comment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Models\Comment;
use App\Models\FeatureRequest;
use App\Services\Comment\MentionService;
use App\Traits\HandleComments;

class FeatureRequestCommentController extends Controller
{
    use HandleComments;

    public function __construct(
        protected MentionService $mentionService
    ) {}

    protected function getMentionService(): MentionService
    {
        return $this->mentionService;
    }

    public function index(FeatureRequest $feature)
    {
        return $this->indexComment($feature);
    }

    public function store(StoreCommentRequest $request, FeatureRequest $feature)
    {
        return $this->storeComment($request, $feature);
    }

    public function destroy(FeatureRequest $feature, Comment $comment)
    {
        return $this->destroyComment($feature, $comment);
    }
}
