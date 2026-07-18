<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\DowntimeLocation\StoreDowntimeLocationRequest;
use App\Http\Requests\DowntimeLocation\UpdateDowntimeLocationRequest;
use App\Http\Resources\DowntimeLocationResource;
use App\Models\DowntimeLocation;
use App\Services\DowntimeLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DowntimeLocationController extends Controller
{
    public function __construct(
        private readonly DowntimeLocationService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $locations = $this->service->getAll(
            filters: $request->only(['search', 'is_active']),
            perPage: $request->integer('per_page', 50)
        );

        return ApiResponse::paginated(
            $locations,
            DowntimeLocationResource::collection($locations),
            'Downtime locations retrieved successfully.'
        );
    }

    public function store(StoreDowntimeLocationRequest $request): JsonResponse
    {
        $location = $this->service->store($request->validated());

        return ApiResponse::success(
            new DowntimeLocationResource($location),
            'Downtime location created successfully.',
            201
        );
    }

    public function show(DowntimeLocation $downtimeLocation): JsonResponse
    {
        return ApiResponse::success(
            new DowntimeLocationResource($downtimeLocation->load('creator:id,name,username')),
            'Downtime location retrieved successfully.'
        );
    }

    public function update(UpdateDowntimeLocationRequest $request, DowntimeLocation $downtimeLocation): JsonResponse
    {
        $location = $this->service->update($downtimeLocation, $request->validated());

        return ApiResponse::success(
            new DowntimeLocationResource($location),
            'Downtime location updated successfully.'
        );
    }

    public function deactivate(DowntimeLocation $downtimeLocation): JsonResponse
    {
        $location = $this->service->deactivate($downtimeLocation);

        return ApiResponse::success(
            new DowntimeLocationResource($location),
            'Downtime location deactivated successfully.'
        );
    }

    public function destroy(DowntimeLocation $downtimeLocation): JsonResponse
    {
        $this->service->delete($downtimeLocation);

        return ApiResponse::success(null, 'Downtime location deleted successfully.');
    }
}
