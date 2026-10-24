<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\SecurityAudit\StoreSecurityAuditFindingRequest;
use App\Http\Requests\SecurityAudit\UpdateSecurityAuditFindingRequest;
use App\Http\Resources\SecurityAudit\SecurityAuditFindingResource;
use App\Models\SecurityAudit;
use App\Models\SecurityAuditFinding;
use App\Services\Ops\SecurityAuditFindingService;
use Illuminate\Http\JsonResponse;

class SecurityAuditFindingController extends Controller
{
    public function __construct(
        private readonly SecurityAuditFindingService $service
    ) {}

    public function index(SecurityAudit $securityAudit): JsonResponse
    {
        $findings = $this->service->getAll($securityAudit);

        return ApiResponse::success(
            SecurityAuditFindingResource::collection($findings),
            'Audit findings retrieved successfully.'
        );
    }

    public function store(StoreSecurityAuditFindingRequest $request, SecurityAudit $securityAudit): JsonResponse
    {
        $finding = $this->service->store($securityAudit, $request->validated());

        return ApiResponse::success(
            new SecurityAuditFindingResource($finding),
            'Audit finding created successfully.',
            201
        );
    }

    public function update(UpdateSecurityAuditFindingRequest $request, SecurityAuditFinding $finding): JsonResponse
    {
        $finding = $this->service->update($finding, $request->validated());

        return ApiResponse::success(
            new SecurityAuditFindingResource($finding),
            'Audit finding updated successfully.'
        );
    }

    public function destroy(SecurityAuditFinding $finding): JsonResponse
    {
        $this->service->delete($finding);

        return ApiResponse::success(null, 'Audit finding deleted successfully.');
    }
}
