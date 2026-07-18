<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\DowntimeComponent\StoreDowntimeComponentRequest;
use App\Http\Requests\DowntimeComponent\SyncDowntimeComponentDependenciesRequest;
use App\Http\Requests\DowntimeComponent\UpdateDowntimeComponentRequest;
use App\Http\Resources\DowntimeComponentResource;
use App\Models\DowntimeComponent;
use App\Services\DowntimeComponentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DowntimeComponentController extends Controller
{
    public function __construct(
        private readonly DowntimeComponentService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $components = $this->service->getAll(
            filters: $request->only(['search', 'category', 'is_active']),
            perPage: $request->integer('per_page', 50)
        );

        return ApiResponse::paginated(
            $components,
            DowntimeComponentResource::collection($components),
            'Downtime components retrieved successfully.'
        );
    }

    public function store(StoreDowntimeComponentRequest $request): JsonResponse
    {
        $component = $this->service->store($request->validated());

        return ApiResponse::success(
            new DowntimeComponentResource($component),
            'Downtime component created successfully.',
            201
        );
    }

    public function show(DowntimeComponent $downtimeComponent): JsonResponse
    {
        $component = $downtimeComponent->load([
            'creator:id,name,username',
            'defaultAffectedComponents:id,code,name,category,is_active',
        ]);

        return ApiResponse::success(
            new DowntimeComponentResource($component),
            'Downtime component retrieved successfully.'
        );
    }

    public function update(UpdateDowntimeComponentRequest $request, DowntimeComponent $downtimeComponent): JsonResponse
    {
        $component = $this->service->update($downtimeComponent, $request->validated());

        return ApiResponse::success(
            new DowntimeComponentResource($component),
            'Downtime component updated successfully.'
        );
    }

    public function syncDependencies(
        SyncDowntimeComponentDependenciesRequest $request,
        DowntimeComponent $downtimeComponent
    ): JsonResponse {
        $component = $this->service->syncDependencies(
            $downtimeComponent,
            $request->validated('default_affected_component_ids')
        );

        return ApiResponse::success(
            new DowntimeComponentResource($component->load('creator:id,name,username')),
            'Downtime component dependencies synced successfully.'
        );
    }

    public function suggestAffected(Request $request): JsonResponse
    {
        $raw = $request->input('source_component_ids', []);
        if (is_string($raw)) {
            $raw = array_filter(array_map('trim', explode(',', $raw)), fn ($v) => $v !== '');
        }

        $validated = validator(
            ['source_component_ids' => $raw],
            [
                'source_component_ids' => ['required', 'array', 'min:1'],
                'source_component_ids.*' => ['integer', 'exists:downtime_components,id'],
            ]
        )->validate();

        $suggested = $this->service->suggestAffected($validated['source_component_ids']);

        return ApiResponse::success(
            DowntimeComponentResource::collection($suggested),
            'Suggested affected components retrieved successfully.'
        );
    }

    public function deactivate(DowntimeComponent $downtimeComponent): JsonResponse
    {
        $component = $this->service->deactivate($downtimeComponent);

        return ApiResponse::success(
            new DowntimeComponentResource($component),
            'Downtime component deactivated successfully.'
        );
    }

    public function destroy(DowntimeComponent $downtimeComponent): JsonResponse
    {
        $this->service->delete($downtimeComponent);

        return ApiResponse::success(null, 'Downtime component deleted successfully.');
    }
}
