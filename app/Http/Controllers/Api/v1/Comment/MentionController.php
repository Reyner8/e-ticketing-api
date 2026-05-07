<?php

namespace App\Http\Controllers\Api\v1\Comment;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Comment\MentionResource;
use App\Models\Comment;
use App\Services\Comment\MentionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MentionController extends Controller
{
    public function __construct(
        private readonly MentionService $mentionService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Comment $comment): JsonResponse
    {
        $mentions = $this->mentionService->getByComment($comment);

        return ApiResponse::success(
            MentionResource::collection($mentions),
            'Comment Mentions Retrieved Successfully'
        );
    }

    public function mine(Request $request): JsonResponse
    {
        $mentions = $this->mentionService->getForUser(
            userId: Auth::id(),
            perPage: $request->integer('per_page', 15)
        );

        return ApiResponse::success(
            MentionResource::collection($mentions),
            'My Mentions Retrieved Successfully'
        );
    }
}
