<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\QualityIndicatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QualityIndicatorController extends Controller
{
    public function __construct(
        private readonly QualityIndicatorService $service
    ) {}

    public function featureRequests(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'application' => ['nullable', 'string', 'max:50'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $report = $this->service->build($validated);

        return ApiResponse::success($report, 'Quality indicator retrieved successfully.');
    }
}
