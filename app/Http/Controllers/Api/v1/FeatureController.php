<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\FeatureRequest\StoreFeatureRequest;
use App\Http\Requests\FeatureRequest\UpdateFeatureRequest;
use App\Enums\UserRole;
use App\Http\Resources\FeatureDetailResource;
use App\Http\Resources\FeatureResource;
use App\Models\FeatureRequest;
use App\Services\FeatureRequestService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeatureController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $feature = FeatureRequest::with(['assignee', 'reporter', 'approver'])
            ->when(
                $user && $user->role === UserRole::Reporter,
                fn($q) => $q->where('reporter_id', $user->id)
            )
            ->when(
                $request->filled('status'),
                fn($q) => $q->where('status', $request->query('status'))
            )
            ->when(
                $request->filled('priority'),
                fn($q) => $q->where('priority', $request->query('priority'))
            )
            ->when(
                $request->filled('request_type'),
                fn($q) => $q->where('request_type', $request->query('request_type'))
            )
            ->when(
                $request->filled('assigned_team'),
                fn($q) => $q->where('assigned_team', $request->query('assigned_team'))
            )
            ->when(
                $request->filled('reporter_id'),
                fn($q) => $q->where('reporter_id', $request->query('reporter_id'))
            )
            ->when(
                $request->filled('search'),
                fn($q) => $q->where('title', 'like', '%' . $request->query('search') . '%')
            )
            ->latest()
            ->paginate(min($request->integer('per_page', 15), 50));

        return ApiResponse::paginated(
            $feature,
            FeatureResource::collection($feature),
            'Feature Request retrieved successfully'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function __construct(private FeatureRequestService $service) {}

    public function store(StoreFeatureRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['id'] = $this->service->generateFeatureRequestId();

        $feature = FeatureRequest::create([
            ...$data,
            'reporter_id' => Auth::id(),
            'date_submitted' => Carbon::now(),
            'status' => 'pending_approval',
            'progress' => 0,
        ]);

        return ApiResponse::success(
            new FeatureDetailResource($feature),
            'Feature Request created successfully',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(FeatureRequest $feature): JsonResponse
    {
        $feature->load(['assignee', 'reporter', 'approver']);

        return ApiResponse::success(
            new FeatureDetailResource($feature),
            'Feature Request retrieved successfully',
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFeatureRequest $request, FeatureRequest $feature): JsonResponse
    {
        $feature->update($request->validated());

        return ApiResponse::success(
            new FeatureDetailResource($feature),
            'Feature Request updated successfully',
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FeatureRequest $feature): JsonResponse
    {
        $feature->delete();

        return ApiResponse::success(
            null,
            'Feature Request deleted successfully'
        );
    }
}
