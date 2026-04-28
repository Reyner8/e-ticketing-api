<?php

namespace App\Traits;

use App\Helpers\ApiResponse;
use App\Http\Requests\StatusHistory\UpdateStatusHistoryRequest;
use App\Http\Resources\StatusHistoryResource;
use App\Services\StatusHistoryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait HandleStatusHistory
{
    abstract protected function getStatusHistoryService(): StatusHistoryService;
    
    public function indexStatusHistory(Request $request, Model $resource): JsonResponse
    {
        $statusHistory = $this->getStatusHistoryService()->getByResource(
            resource: $resource,
            perPage: $request->integer('per_page', 15)
        );

        return ApiResponse::paginated(
            $statusHistory,
            StatusHistoryResource::collection($statusHistory),
            'Status History Retrieved Successfully'
        );
    }

    public function updateStatus(UpdateStatusHistoryRequest $request, Model $resource)
    {
        $statusHistory = $this->getStatusHistoryService()->update(
            resource: $resource,
            newStatus: $request->validated('status'),
            extra: [
                'reason' => $request->validated('reason'),
                'notes' => $request->validated('notes'),
            ]
        );

        return ApiResponse::success(
            new StatusHistoryResource($statusHistory->load('changer')),
            'Status Updated Successfully'
        );

    }
}