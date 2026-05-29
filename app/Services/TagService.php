<?php

namespace App\Services;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class TagService
{
    public function getAll(string $search = '', int $perPage = 15): LengthAwarePaginator
    {
        return Tag::query()
        ->when(
            ! empty($search),
            fn ($q) => $q->where('name', 'like', '%' . $search . '%')
        )
        ->orderBy('name')
        ->paginate(min($perPage, 50));
    }

    public function store(array $data): Tag
    {
        return Tag::create($data);
    }

    public function update(Tag $tag, array $data): Tag

    {
        $tag->update($data);
        
        return $tag;
    }

    public function delete(Tag $tag): void
    {
        $usageCount = $tag->tickets()->count()
        + $tag->featureRequests()->count()
        + $tag->errorReports()->count();

        if ($usageCount > 0) {
            throw ValidationException::withMessages([
                'tag' => [
                    "Tag '{$tag->name}' is still used by {$usageCount} resource(s) and cannot be deleted."
                ]
            ]);
        }

        $tag->delete();
    }

    public function attachTags(Model $resource, array $tagIds): Collection
    {
        $existingTagIds = $resource->tags()->pluck('tags.id')->toArray();
        $newTagIds = array_diff($tagIds, $existingTagIds);
        
        if (empty($newTagIds)) {
            throw ValidationException::withMessages([
                'tag_ids' => ['All selected tags are already attached to this resource.']
            ]);
        }

        $resource->tags()->attach($newTagIds);

        return $resource->tags()->orderBy('name')->get();
    }

    public function detachTags(Model $resource, array $tagIds): Collection
    {
        $existingTagIds = $resource->tags()->pluck('tags.id')->toArray();
        $validTagIds = array_intersect($tagIds, $existingTagIds);

        if (empty($validTagIds)) {
            throw ValidationException::withMessages([
                'tag_ids' => ['None of the selected tags are attached to this resource.']
            ]);
        }

        $resource->tags()->detach($validTagIds);

        return $resource->tags()->orderBy('name')->get();
    }

    public function syncTags(Model $resource, array $tagIds): Collection
    {
        $resource->tags()->sync($tagIds);

        return $resource->tags()->orderBy('name')->get();
    }

    public function getByResource(Model $resource): Collection
    {
        return $resource->tags()->orderBy('name')->get();
    }
}