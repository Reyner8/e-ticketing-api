<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\SecurityAudit\StoreSecurityAuditRequest;
use App\Http\Requests\SecurityAudit\UpdateSecurityAuditRequest;
use App\Http\Resources\SecurityAudit\SecurityAuditResource;
use App\Models\SecurityAudit;
use App\Services\Ops\SecurityAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecurityAuditController extends Controller
{
    public function __construct(
        private readonly SecurityAuditService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $audits = $this->service->getAll(
            filters: $request->only(['type', 'status', 'from', 'to', 'search']),
            perPage: $request->integer('per_page', 15)
        );

        return ApiResponse::paginated(
            $audits,
            SecurityAuditResource::collection($audits),
            'Security audits retrieved successfully.'
        );
    }

    public function store(StoreSecurityAuditRequest $request): JsonResponse
    {
        $audit = $this->service->store($request->validated());

        return ApiResponse::success(
            new SecurityAuditResource($audit),
            'Security audit created successfully.',
            201
        );
    }

    public function show(SecurityAudit $securityAudit): JsonResponse
    {
        return ApiResponse::success(
            new SecurityAuditResource($this->service->loadRecord($securityAudit)),
            'Security audit retrieved successfully.'
        );
    }

    public function update(UpdateSecurityAuditRequest $request, SecurityAudit $securityAudit): JsonResponse
    {
        $audit = $this->service->update($securityAudit, $request->validated());

        return ApiResponse::success(
            new SecurityAuditResource($audit),
            'Security audit updated successfully.'
        );
    }

    public function destroy(SecurityAudit $securityAudit): JsonResponse
    {
        $this->service->delete($securityAudit);

        return ApiResponse::success(null, 'Security audit deleted successfully.');
    }
}
