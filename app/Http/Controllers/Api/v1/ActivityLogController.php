<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ErrorReport;
use App\Models\FeatureRequest;
use App\Models\Ticket;
use App\Services\Log\ActivityLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $logService
    ) {}
    
    public function ticket(Request $request, Ticket $ticket) : JsonResponse 
    {
        return $this->getActivityLogs($request, $ticket);    
    }

    public function errorReport(Request $request, ErrorReport $error): JsonResponse
    {
        return $this->getActivityLogs($request, $error);
    }

    public function featureRequest(Request $request, FeatureRequest $feature): JsonResponse
    {
        return $this->getActivityLogs($request, $feature);
    }

    public function index(Request $request): JsonResponse
    {
        $logs = $this->logService->getRecent(
            perPage: $request->integer('per_page', 20),
            action: $request->string('action')->value() ?: null
        );

        return ApiResponse::paginated(
            $logs,
            ActivityLogResource::collection($logs),
            'Recent activity retrieved successfully'
        );
    }

    // private
    private function getActivityLogs(Request $request, Model $loggable): JsonResponse
    {
        $logs = $this->logService->getByResource(
            loggable: $loggable,
            perPage: $request->integer('per_page', 15),
            action: $request->string('action')->value() ?: null
        );

        return ApiResponse::paginated(
            $logs,
            ActivityLogResource::collection($logs),
            'Activity logs retrieved successfully'
        );
    }
}
