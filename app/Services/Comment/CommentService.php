<?php

namespace App\Services\Comment;

use App\Enums\ActivityAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Comment\CommentResource;
use App\Http\Requests\Comment\StoreCommentRequest;
use Illuminate\Support\Facades\DB;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Helpers\ApiResponse;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Log\ActivityLogService;
use App\Services\NotificationService;
use Illuminate\Support\Str;

class CommentService
{
    public function __construct(
        private readonly MentionService $mentionService,
        private readonly ActivityLogService $logService,
        private readonly NotificationService $notificationService,
    ) {}

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
        $mentionedUsers = $this->mentionService->resolve(
            content: $request->validated('content'),
            authorId: Auth::id()
        );

        $comment = DB::transaction(
            function () use ($request, $parent, $mentionedUsers) {
                $comment = $parent->comments()->create([
                    ...$request->validated(),
                    'user_id' => Auth::id(),
                    'is_internal' => $this->resolveIsInternal($request)
                ]);

                $this->mentionService->persist($comment->id, $mentionedUsers);

                return $comment;
            }
        );

        //* log commented
        $this->logService->logCommented(
            loggable: $parent,
            preview: Str::limit($request->validated('content'), 100)
        );

        //* log mentioned
        foreach ($mentionedUsers as $mentionedUser) {
            $this->logService->logMentioned(
                loggable: $parent,
                targetUserId: $mentionedUser->id,
                targetUserName: $mentionedUser->name
            );
        }

        //* mention notification
        if ($parent instanceof Ticket && $mentionedUsers->isNotEmpty()) {
            $mentionerName = Auth::user()?->name ?? 'Someone';

            foreach ($mentionedUsers as $mentionedUser) {
                $this->notificationService->notifyCommentMention(
                    userId: $mentionedUser->id,
                    ticket: $parent,
                    mentionedBy: $mentionerName
                );
            }
        }

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

        if (! $user?->isItStaff()) {
            return false;
        }

        return $request->boolean('is_internal');
    }
}
