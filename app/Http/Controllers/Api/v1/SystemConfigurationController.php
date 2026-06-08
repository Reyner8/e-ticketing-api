<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\SystemConfig\StoreSystemConfigurationRequest;
use App\Http\Requests\SystemConfig\UpdateSystemConfigurationRequest;
use App\Http\Resources\SystemConfigurationResource;
use App\Models\SystemConfiguration;
use App\Services\SystemConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemConfigurationController extends Controller
{
    public function __construct(
        private readonly SystemConfigurationService $configService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $configs = $this->configService->getAll(
            search: $request->string('search')->value(),
            perPage: $request->integer('per_page', 15)
        );

        return ApiResponse::paginated(
            $configs,
            SystemConfigurationResource::collection($configs),
            'System configurations retrieved successfully.'
        );
    }

    public function store(StoreSystemConfigurationRequest $request): JsonResponse
    {
        $config = $this->configService->store($request->validated());

        return ApiResponse::success(
            new SystemConfigurationResource($config),
            'System configuration created successfully.',
            201
        );
    }

    public function show(SystemConfiguration $config): JsonResponse
    {
        return ApiResponse::success(
            new SystemConfigurationResource($config->load('updater')),
            'System configuration retrieved successfully.'
        );
    }

    public function showByKey(string $key): JsonResponse
    {
        $config = $this->configService->getValue($key);

        return ApiResponse::success(
            new SystemConfigurationResource($config),
            'System configuration retrieved successfully.'
        );
    }

    public function update(UpdateSystemConfigurationRequest $request, SystemConfiguration $config): JsonResponse
    {
        $updated = $this->configService->update($config, $request->validated());

        return ApiResponse::success(
            new SystemConfigurationResource($updated),
            'System configuration updated successfully.'
        );
    }

    public function upsertByKey(UpdateSystemConfigurationRequest $request, string $key): JsonResponse
    {
        $config = $this->configService->upsert(
            key: $key,
            value: $request->validated('config_value'),
            description: $request->validated('description')
        );

        return ApiResponse::success(
            new SystemConfigurationResource($config),
            'System configuration upserted successfully'
        );
    }

    public function destroy(SystemConfiguration $config): JsonResponse
    {
        $this->configService->delete($config);

        return ApiResponse::success(
            null,
            'System configuration deleted successfully.'
        );
    }

    public function clearCache(): JsonResponse
    {
        $this->configService->clearCache();

        return ApiResponse::success(
            null,
            'Configuration cache cleared successfully'
        );
    }
}
