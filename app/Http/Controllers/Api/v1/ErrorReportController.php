<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\UserRole;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\ErrorReport\StoreErrorReportRequest;
use App\Http\Requests\ErrorReport\UpdateErrorReportRequest;
use App\Http\Resources\ErrorReport\ErrorReportResource;
use App\Http\Resources\ErrorReport\ErrorReportResourceDetail;
use App\Models\ErrorReport;
use App\Services\ErrorReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ErrorReportController extends Controller
{
    public function __construct(
        private readonly ErrorReportService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $errors = $this->service->getAll(
            filters: $request->only([
                'status', 'priority',
                'category', 'assigned_team',
                'reporter_id', 'sla_breached',
                'overdue', 'is_direct_input',
                'search'
            ]),
            perPage: $request->integer('per_page', 15)
        );

        return ApiResponse::paginated(
            $errors,
            ErrorReportResource::collection($errors),
            'Error reports retrieved successfully.'
        );
    }

    public function store(StoreErrorReportRequest $request): JsonResponse 
    {
        $error = $this->service->store($request->validated());
        
        return ApiResponse::success(
            new ErrorReportResourceDetail($error),
            'Error report created successfully.',
            201
        );
    }

    public function show(ErrorReport $error): JsonResponse 
    {
        $user = Auth::user();

        if ($user->role === UserRole::Reporter && $user->id !== $error->reporter_id) {
            abort(403, 'You are not authorized to view this error report.');
        }
        
        return ApiResponse::success(
            new ErrorReportResourceDetail($error->load(['reporter', 'assignedUser', 'sourceTicket:id,title,status'])),
            'Error report retrieved successfully.'
        );
    }

    public function update(UpdateErrorReportRequest $request, ErrorReport $error): JsonResponse
    {
        $updated = $this->service->update($error, $request->validated());

        return ApiResponse::success(
            new ErrorReportResourceDetail($updated),
            'Error report updated successfully.'
        );
    }

    public function destroy(ErrorReport $error): JsonResponse
    {
        $this->service->delete($error);

        return ApiResponse::success(
            null,
            'Error report deleted successfully.'
        );
    }
}
