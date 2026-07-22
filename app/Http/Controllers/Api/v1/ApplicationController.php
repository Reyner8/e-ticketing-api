<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Application\StoreApplicationRequest;
use App\Http\Requests\Application\UpdateApplicationRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Services\ApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function __construct(
        private readonly ApplicationService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $applications = $this->service->getAll(
            filters: $request->only(['search', 'is_active']),
            perPage: $request->integer('per_page', 50)
        );

        return ApiResponse::paginated(
            $applications,
            ApplicationResource::collection($applications),
            'Applications retrieved successfully.'
        );
    }

    public function store(StoreApplicationRequest $request): JsonResponse
    {
        $application = $this->service->store($request->validated());

        return ApiResponse::success(
            new ApplicationResource($application),
            'Application created successfully.',
            201
        );
    }

    public function show(Application $application): JsonResponse
    {
        return ApiResponse::success(
            new ApplicationResource($application->load('creator:id,name,username')),
            'Application retrieved successfully.'
        );
    }

    public function update(UpdateApplicationRequest $request, Application $application): JsonResponse
    {
        $application = $this->service->update($application, $request->validated());

        return ApiResponse::success(
            new ApplicationResource($application),
            'Application updated successfully.'
        );
    }

    public function deactivate(Application $application): JsonResponse
    {
        $application = $this->service->deactivate($application);

        return ApiResponse::success(
            new ApplicationResource($application),
            'Application deactivated successfully.'
        );
    }

    public function destroy(Application $application): JsonResponse
    {
        $this->service->delete($application);

        return ApiResponse::success(null, 'Application deleted successfully.');
    }
}
