<?php

namespace App\Traits;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

/** @mixin Model */
trait HasTags
{
    public function tags(): BelongsToMany
    {
        /** @var Model $this  */

        return $this->belongsToMany(
            Tag::class,
            $this->resolvePivotTable(),
            $this->resolveForeignKey(),
            'tag_id'
        );
    }

    // Helpers
    public function hasTags(): bool
    {
        return $this->tags()->exists();
    }

    public function hasTag(string $tagId): bool
    {
        return $this->tags()->where('tags.id', $tagId)->exists();
    }

    // Private
    private function resolvePivotTable(): string
    {
        $modelName = class_basename(static::class);

        $snakeCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $modelName));

        return $snakeCase . '_tags';
    }

    private function resolveForeignKey(): string
    {
        $modelName = class_basename(static::class);

        $snakeCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $modelName));

        return $snakeCase . '_id';
    }
}
