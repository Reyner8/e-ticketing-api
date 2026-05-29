<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\StoreTagRequest;
use App\Http\Requests\Tag\SyncTagRequest;
use App\Http\Requests\Tag\UpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Models\ErrorReport;
use App\Models\FeatureRequest;
use App\Models\Tag;
use App\Models\Ticket;
use App\Services\TagService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psy\Util\Json;

class TagController extends Controller
{
    public function __construct(
        protected readonly TagService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tags = $this->service->getAll(
            search: $request->string('search')->value(),
            perPage: $request->integer('per_page', 15)
        );

        return ApiResponse::paginated(
            $tags,
            TagResource::collection($tags),
            'Tags Retrieved Successfully'
        );
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        $tags = $this->service->store($request->validated());

        return ApiResponse::success(
            new TagResource($tags),
            'Tag created successfully',
            201
        );
    }

    public function show(Tag $tag): JsonResponse
    {
        return ApiResponse::success(
            new TagResource($tag),
            'Tags Retrieved Successfully'
        );
    }

    public function update(UpdateTagRequest $request, Tag $tag): JsonResponse
    {
        $updated = $this->service->update($tag, $request->validated());

        return ApiResponse::success(
            new TagResource($updated),
            'Tag updated successfully'
        );
    }

    public function destroy(Tag $tag): JsonResponse
    {
        $this->service->delete($tag);

        return ApiResponse::success(
            null,
            'Tag deleted successfully'
        );
    }

    public function indexByResource(string $resourceType, string $resourceId): JsonResponse
    {
        $resource = $this->resolveResource($resourceType, $resourceId);
        $tags = $this->service->getByResource($resource);

        return ApiResponse::success(
            TagResource::collection($tags),
            'Tags retrieved successfully'
        );
    }

    public function attach(SyncTagRequest $request, string $resourceType, string $resourceId): JsonResponse
    {
        $resource = $this->resolveResource($resourceType, $resourceId);
        $tags = $this->service->attachTags($resource, $request->validated('tag_ids'));

        return ApiResponse::success(
            TagResource::collection($tags),
            'Tags attached successfully'
        );
    }

    public function detach(SyncTagRequest $request, string $resourceType, string $resourceId): JsonResponse
    {
        $resource = $this->resolveResource($resourceType, $resourceId);
        $tags = $this->service->detachTags($resource, $request->validated('tag_ids'));

        return ApiResponse::success(
            TagResource::collection($tags),
            'Tags detached successfully'
        );
    }

    public function sync(SyncTagRequest $request, string $resourceType, string $resourceId) : JsonResponse 
    {
        $resource = $this->resolveResource($resourceType, $resourceId);
        $tags = $this->service->syncTags($resource, $request->validated('tag_ids'));

        return ApiResponse::success(
            TagResource::collection($tags),
            'Tags synced successfully'
        );
    }

    private function resolveResource(string $resourceType, string $resourceId) : Model 
    {
        return match ($resourceType) {
            'tickets' => Ticket::findOrFail($resourceId),
            'errors' => ErrorReport::findOrFail($resourceId),
            'features' => FeatureRequest::findOrFail($resourceId),
            default => abort(404, "Resource type '{$resourceType}' not found.")
        };    
    }
}
