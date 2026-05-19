<?php

namespace App\Traits;

use App\Helpers\ApiResponse;
use App\Http\Requests\RejectRequest;
use App\Http\Resources\ApprovalResource;
use App\Services\ApprovalService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

trait HandleApproval
{
    abstract protected function getApprovalService(): ApprovalService;

    public function approve(Model $resource): JsonResponse
    {
        $updated = $this->getApprovalService()->approve(
            resource: $resource
        );

        return ApiResponse::success(
            new ApprovalResource($updated),
            class_basename($resource) . ' approved successfully.'
        );
    }

    public function reject(RejectRequest $request, Model $resource): JsonResponse
    {
        $updated = $this->getApprovalService()->reject(
            resource: $resource,
            reason: $request->validated('rejection_reason')
        );

        return ApiResponse::success(
            new ApprovalResource($updated),
            class_basename($resource) . ' rejected successfully.'
        );
    }
}