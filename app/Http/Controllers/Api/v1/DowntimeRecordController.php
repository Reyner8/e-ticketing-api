<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\DowntimeRecord\ResolveDowntimeRecordRequest;
use App\Http\Requests\DowntimeRecord\StoreDowntimeRecordRequest;
use App\Http\Requests\DowntimeRecord\UpdateDowntimeRecordRequest;
use App\Http\Resources\DowntimeRecordResource;
use App\Models\DowntimeRecord;
use App\Services\DowntimeAnalyticsService;
use App\Services\DowntimeRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DowntimeRecordController extends Controller
{
    public function __construct(
        private readonly DowntimeRecordService $service,
        private readonly DowntimeAnalyticsService $analyticsService
    ) {}

    public function analytics(Request $request): JsonResponse
    {
        $data = $this->analyticsService->summarize(
            $request->only([
                'from_date',
                'to_date',
                'location_id',
                'component_id',
                'category',
                'type',
                'status',
                'impact',
            ])
        );

        return ApiResponse::success(
            $data,
            'Downtime analytics retrieved successfully.'
        );
    }

    public function index(Request $request): JsonResponse
    {
        $downtimeRecord = $this->service->getAll(
            filters: $request->only([
                'type',
                'status',
                'impact',
                'from_date',
                'to_date',
                'location_id',
                'component_id',
                'category',
            ]),
            perPage: $request->integer('per_page', 15)
        );

        return ApiResponse::paginated(
            $downtimeRecord,
            DowntimeRecordResource::collection($downtimeRecord),
            'Downtime records retrieved successfully'
        );
    }

    public function store(StoreDowntimeRecordRequest $request): JsonResponse
    {
        $downtimeRecord = $this->service->store($request->validated());

        return ApiResponse::success(
            new DowntimeRecordResource($downtimeRecord),
            'Downtime record created successfully',
            201
        );
    }

    public function show(DowntimeRecord $downtimeRecord): JsonResponse
    {
        return ApiResponse::success(
            new DowntimeRecordResource($this->service->loadRecord($downtimeRecord)),
            'Downtime record retrieved successfully'
        );
    }

    public function update(UpdateDowntimeRecordRequest $request, DowntimeRecord $downtimeRecord): JsonResponse
    {
        $downtimeRecord = $this->service->update($downtimeRecord, $request->validated());

        return ApiResponse::success(
            new DowntimeRecordResource($downtimeRecord),
            'Downtime record updated successfully'
        );
    }

    public function resolve(ResolveDowntimeRecordRequest $request, DowntimeRecord $downtimeRecord): JsonResponse
    {
        $downtimeRecord = $this->service->resolve($downtimeRecord, $request->validated());

        return ApiResponse::success(
            new DowntimeRecordResource($downtimeRecord),
            'Downtime record resolved successfully'
        );
    }

    public function destroy(DowntimeRecord $downtimeRecord): JsonResponse
    {
        $this->service->delete($downtimeRecord);

        return ApiResponse::success(
            null,
            'Downtime record deleted successfully'
        );
    }
}
