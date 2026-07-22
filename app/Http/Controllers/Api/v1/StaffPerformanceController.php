<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\StaffPerformanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffPerformanceController extends Controller
{
    public function __construct(
        private readonly StaffPerformanceService $service
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'team' => ['nullable', 'string', Rule::in(['programmer', 'network'])],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'section' => ['nullable', 'string', Rule::in(StaffPerformanceService::SECTIONS)],
        ]);

        $report = $this->service->build($validated);

        return ApiResponse::success($report, 'Staff performance retrieved successfully.');
    }
}
