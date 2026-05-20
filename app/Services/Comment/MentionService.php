<?php

namespace App\Services\Comment;

use App\Exceptions\MentionLimitExceededException;
use App\Models\Comment;
use App\Models\CommentMention;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;


class MentionService
{
    private const max_mentions_per_comment = 10;
    private const mention_pattern = '/@([a-zA-Z0-9_.-]{1,50})/';

    public function resolve(string $content, int $authorId): Collection
    {
        $usernames = $this->extractUsernames($content);

        $this->guardAgainstMentionLimit($usernames);

        return $this->fetchValidUsers($usernames, $authorId);
    }

    public function persist(int $commentId, Collection $mentionedUser)
    {
        if ($mentionedUser->isEmpty()) {
            return;
        }

        $rows = $mentionedUser->map(fn(User $user) => [
            'comment_id' => $commentId,
            'user_id' => $user->id,
        ])->toArray();

        CommentMention::insert($rows);
    }

    public function getByComment(Comment $comment)
    {
        return $comment->mentions()->with('mentionedUser:id,name,username')->get();
    }

    public function getForUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return CommentMention::where('user_id', $userId)
            ->with(
                [
                    'comment:id,commentable_id,commentable_type,user_id,content,is_internal,created_at',
                    'comment.user:id,name,username',
                    'comment.commentable'
                ]
            )->latest('id')
            ->paginate(min($perPage, 50));
    }

    // Helper
    private function extractUsernames(string $content): array
    {
        preg_match_all(self::mention_pattern, $content, $matches);

        return array_values(
            array_unique(
                array_map('strtolower', $matches[1] ?? [])
            )
        );
    }

    private function guardAgainstMentionLimit(array $usernames)
    {
        if (count($usernames) > self::max_mentions_per_comment) {
            throw new MentionLimitExceededException(
                limit: self::max_mentions_per_comment,
                given: count($usernames)
            );
        }
    }

    private function fetchValidUsers(array $usernames, int $authorId): Collection
    {
        if (empty($usernames)) {
            return collect();
        }

        return User::whereIn('username', $usernames)
            ->where('id', '!=', $authorId)
            ->where('is_active', true)
            ->get(['id', 'username', 'name']);
    }
}
