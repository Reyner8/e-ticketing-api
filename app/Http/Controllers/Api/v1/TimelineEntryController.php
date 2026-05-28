<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\TimelineEntries\StoreTimelineEntryRequest;
use App\Http\Requests\TimelineEntries\UpdateTimelineEntryProgressRequest;
use App\Http\Requests\TimelineEntries\UpdateTimelineEntryRequest;
use App\Http\Resources\TimelineEntry\TimelineEntryDetailResource;
use App\Http\Resources\TimelineEntry\TimelineEntryResource;
use App\Models\FeatureRequest;
use App\Models\TimelineEntry;
use App\Services\TimelineEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimelineEntryController extends Controller
{
    public function __construct(
        protected readonly TimelineEntryService $service
    ) {}

    public function index(Request $request, FeatureRequest $feature): JsonResponse
    {
        $entries = $this->service->getByFeatureRequest(
            feature: $feature,
            filters: $request->only(['phase', 'is_completed', 'assigned_to']),
            perPage: $request->integer('per_page', 15)
        );

        return ApiResponse::paginated(
            $entries,
            TimelineEntryResource::collection($entries),
            'Timeline entries retrieved successfully'
        );
    }

    public function store(StoreTimelineEntryRequest $request, FeatureRequest $feature): JsonResponse
    {
        $entry = $this->service->store($feature, $request->validated());

        return ApiResponse::success(
            new TimelineEntryDetailResource($entry),
            'Timeline entry created successfully',
            201
        );
    }

    public function show(FeatureRequest $feature, TimelineEntry $entry): JsonResponse
    {
        $this->guardBelongsToFeatureRequest($feature, $entry);

        return ApiResponse::success(
            new TimelineEntryDetailResource($entry->load('assignee')),
            'Timeline entry retrieved successfully'
        );
    }

    public function update(UpdateTimelineEntryRequest $request, FeatureRequest $feature, TimelineEntry $entry): JsonResponse
    {
        $this->guardBelongsToFeatureRequest($feature, $entry);

        $updated = $this->service->update($entry, $request->validated());

        return ApiResponse::success(
            new TimelineEntryDetailResource($updated),
            'Timeline entry updated successfully'
        );
    }

    public function updateProgress(UpdateTimelineEntryProgressRequest $request, FeatureRequest $feature, TimelineEntry $entry): JsonResponse
    {
        $this->guardBelongsToFeatureRequest($feature, $entry);

        $updated = $this->service->updateProgress($entry, $request->validated('progress'));

        return ApiResponse::success(
            new TimelineEntryDetailResource($updated),
            'Timeline entry progress updated successfully'
        );
    }

    public function complete(FeatureRequest $feature, TimelineEntry $entry): JsonResponse
    {
        $this->guardBelongsToFeatureRequest($feature, $entry);

        $updated = $this->service->complete($entry);

        return ApiResponse::success(
            new TimelineEntryDetailResource($updated),
            'Timeline entry marked as completed'
        );
    }

    public function destroy(FeatureRequest $feature, TimelineEntry $entry): JsonResponse
    {
        $this->guardBelongsToFeatureRequest($feature, $entry);

        $this->service->delete($entry);

        return ApiResponse::success(
            null,
            'Timeline entry deleted successfully'
        );
    }

    // Helpers
    private function guardBelongsToFeatureRequest(FeatureRequest $feature, TimelineEntry $entry): void
    {
        if ((string) $entry->feature_request_id !== (string) $feature->id) {
            abort(403, 'This timeline entry does not belong to that feature request.');
        }
    }
}
