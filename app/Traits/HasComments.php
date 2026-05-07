<?php

namespace App\Traits;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasComments
{

    public function comments(): MorphMany
    {
        /** @var Model $this  */

        return $this->morphMany(Comment::class, 'commentable');
    }

    public function publicComments()
    {
        /** @var Model $this  */

        return $this->morphMany(Comment::class, 'commentable')->where('is_internal', false);
    }

    public function internalComments()
    {
        /** @var Model $this  */

        return $this->morphMany(Comment::class, 'commentable')->where('is_internal', true);
    }

    // Helpers
    public function hasComments(): bool
    {
        return $this->comments()->exists();
    }

    public function commentCounts(): int
    {
        return $this->comments()->count();
    }
}
