<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\DowntimeRecord\ResolveDowntimeRecordRequest;
use App\Http\Requests\DowntimeRecord\StoreDowntimeRecordRequest;
use App\Http\Requests\DowntimeRecord\UpdateDowntimeRecordRequest;
use App\Http\Resources\DowntimeRecordResource;
use App\Models\DowntimeRecord;
use App\Services\DowntimeRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DowntimeRecordController extends Controller
{
    public function __construct(
        private readonly DowntimeRecordService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $downtimeRecord = $this->service->getAll(
            filters: $request->only(['type', 'status', 'impact', 'from_date', 'to_date']),
            perPage: $request->integer('per_page', 15)
        );

        return ApiResponse::paginated(
            $downtimeRecord,
            DowntimeRecordResource::collection($downtimeRecord),
            'Downtime downtimeRecord retrieved successfully'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDowntimeRecordRequest $request): JsonResponse
    {
        $downtimeRecord = $this->service->store($request->validated());

        return ApiResponse::success(
            new DowntimeRecordResource($downtimeRecord),
            'Downtime record created successfully',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(DowntimeRecord $downtimeRecord): JsonResponse
    {
        return ApiResponse::success(
            new DowntimeRecordResource($downtimeRecord->load('reporter')),
            'Downtime record retrieved successfully'
        );
    }

    /**
     * Update the specified resource in storage.
     */
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DowntimeRecord $downtimeRecord): JsonResponse
    {
        $this->service->delete($downtimeRecord);

        return ApiResponse::success(
            null,
            'Downtime record deleted successfully'
        );
    }
}
