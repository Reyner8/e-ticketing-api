<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackupRestoreTest\StoreBackupRestoreTestRequest;
use App\Http\Requests\BackupRestoreTest\UpdateBackupRestoreTestRequest;
use App\Http\Resources\BackupRestoreTest\BackupRestoreTestResource;
use App\Models\BackupRestoreTest;
use App\Services\Ops\BackupRestoreTestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BackupRestoreTestController extends Controller
{
    public function __construct(
        private readonly BackupRestoreTestService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tests = $this->service->getAll(
            filters: $request->only(['result', 'restore_type', 'from', 'to', 'search']),
            perPage: $request->integer('per_page', 15)
        );

        return ApiResponse::paginated(
            $tests,
            BackupRestoreTestResource::collection($tests),
            'Backup restore tests retrieved successfully.'
        );
    }

    public function store(StoreBackupRestoreTestRequest $request): JsonResponse
    {
        $test = $this->service->store($request->validated());

        return ApiResponse::success(
            new BackupRestoreTestResource($test),
            'Backup restore test created successfully.',
            201
        );
    }

    public function show(BackupRestoreTest $restoreTest): JsonResponse
    {
        return ApiResponse::success(
            new BackupRestoreTestResource($restoreTest->load(['performer', 'creator'])),
            'Backup restore test retrieved successfully.'
        );
    }

    public function update(UpdateBackupRestoreTestRequest $request, BackupRestoreTest $restoreTest): JsonResponse
    {
        $test = $this->service->update($restoreTest, $request->validated());

        return ApiResponse::success(
            new BackupRestoreTestResource($test),
            'Backup restore test updated successfully.'
        );
    }

    public function destroy(BackupRestoreTest $restoreTest): JsonResponse
    {
        $this->service->delete($restoreTest);

        return ApiResponse::success(null, 'Backup restore test deleted successfully.');
    }
}
