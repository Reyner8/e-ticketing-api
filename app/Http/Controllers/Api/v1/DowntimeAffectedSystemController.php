<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\DowntimeAffectedSystem\StoreDowntimeAffectedSystemRequest;
use App\Models\DowntimeRecord;
use App\Services\DowntimeAffectedSystemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DowntimeAffectedSystemController extends Controller
{
    public function __construct(
        private readonly DowntimeAffectedSystemService $downtimeService
    ) {}

    public function index(DowntimeRecord $downtime): JsonResponse
    {
        $systems = $this->downtimeService->getSystems($downtime);

        return ApiResponse::success(
            $systems->pluck('system_name'),
            'Affected system retrieved successfully'
        );
    } 

    public function store(StoreDowntimeAffectedSystemRequest $request, DowntimeRecord $downtime): JsonResponse
    {
        $systems = $this->downtimeService->addSystems(
            downtime: $downtime,
            systemNames: $request->validated('system_names')
        );

        return ApiResponse::success(
            $systems->pluck('system_name'),
            'Affected system added successfully',
            201
        );
    }

    public function sync(StoreDowntimeAffectedSystemRequest $request, DowntimeRecord $downtime): JsonResponse
    {
        $systems = $this->downtimeService->syncSystems(
            downtime: $downtime,
            systemNames: $request->validated('system_names')
        );

        return ApiResponse::success(
            $systems->pluck('system_name'),
            'Affected system sync successfully.'
        );
    }

    public function destroy(Request $request, DowntimeRecord $downtime): JsonResponse
    {
        $validated = $request->validate([
            'system_names' => ['required', 'array', 'min:1'],
            'system_names.*' => ['required', 'string']
        ]);
        
        $systems = $this->downtimeService->removeSystem(
            downtime: $downtime,
            systemNames: $validated['system_names']
        );

        return ApiResponse::success(
            $systems->pluck('system_name'),
            'Affected systems removed successfully.'
        );
    }
}
