<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServerRoomInspection\StoreServerRoomInspectionRequest;
use App\Http\Requests\ServerRoomInspection\UpdateServerRoomInspectionRequest;
use App\Http\Resources\ServerRoomInspection\ServerRoomInspectionResource;
use App\Models\ServerRoomInspection;
use App\Services\Ops\ServerRoomInspectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServerRoomInspectionController extends Controller
{
    public function __construct(
        private readonly ServerRoomInspectionService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $inspections = $this->service->getAll(
            filters: $request->only(['inspection_type', 'conclusion', 'from', 'to', 'search']),
            perPage: $request->integer('per_page', 15)
        );

        return ApiResponse::paginated(
            $inspections,
            ServerRoomInspectionResource::collection($inspections),
            'Server room inspections retrieved successfully.'
        );
    }

    public function store(StoreServerRoomInspectionRequest $request): JsonResponse
    {
        $inspection = $this->service->store($request->validated());

        return ApiResponse::success(
            new ServerRoomInspectionResource($inspection),
            'Server room inspection created successfully.',
            201
        );
    }

    public function show(ServerRoomInspection $inspection): JsonResponse
    {
        return ApiResponse::success(
            new ServerRoomInspectionResource($inspection->load(['inspector', 'creator'])),
            'Server room inspection retrieved successfully.'
        );
    }

    public function update(UpdateServerRoomInspectionRequest $request, ServerRoomInspection $inspection): JsonResponse
    {
        $inspection = $this->service->update($inspection, $request->validated());

        return ApiResponse::success(
            new ServerRoomInspectionResource($inspection),
            'Server room inspection updated successfully.'
        );
    }

    public function destroy(ServerRoomInspection $inspection): JsonResponse
    {
        $this->service->delete($inspection);

        return ApiResponse::success(null, 'Server room inspection deleted successfully.');
    }
}
