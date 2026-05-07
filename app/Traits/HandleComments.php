<?php

namespace App\Traits;

use App\Helpers\ApiResponse;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Resources\Comment\CommentResource;
use App\Models\Comment;
use App\Models\User;
use App\Services\Comment\MentionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait HandleComments
{
    public function indexComment(Model $parent)
    {
        /** @var User|null $user */
        $user = Auth::user();

        $withInternal = $user && $user->isItStaff();

        $comments = $parent->comments()
            ->with([
                'user:id,name,username',
                'mentions.mentionedUser:id,name,username'
            ])
            ->when(! $withInternal, fn($q) => $q->where('is_internal', false))
            ->latest('created_at')
            ->paginate(10);

        return CommentResource::collection($comments);
    }

    public function storeComment(StoreCommentRequest $request, Model $parent)
    {
        $mentionService = app(MentionService::class);

        $mentionedUser = $mentionService->resolve(
            content: $request->validated('content'),
            authorId: Auth::id()
        );

        $comment = DB::transaction(
            function () use ($request, $parent, $mentionService, $mentionedUser) {
                $comment = $parent->comments()->create([
                    ...$request->validated(),
                    'user_id' => Auth::id(),
                    'is_internal' => $this->resolveIsInternal($request)
                ]);

                $mentionService->persist($comment->id, $mentionedUser);

                return $comment;
            }
        );

        return new CommentResource(
            $comment->load(['user', 'mentions.mentionedUser'])
        );
    }

    public function destroyComment(Model $parent, Comment $comment): JsonResponse
    {
        $morphAlias = Relation::getMorphAlias(get_class($parent));

        if ($comment->commentable_id !== $parent->getKey() || $comment->commentable_type !== $morphAlias) {
            abort(403, 'This comment does not belong to that resource');
        }

        $comment->delete();

        return ApiResponse::success(
            null,
            'Comment deleted successfully'
        );
    }

    // Helpers
    private function resolveIsInternal(StoreCommentRequest $request): bool
    {
        $user = $request->user();

        if ($user && $user->isItStaff()) {
            return true;
        }

        return $request->boolean('is_internal');
    }
}
