<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServerRoomVisitor\StoreServerRoomVisitorRequest;
use App\Http\Requests\ServerRoomVisitor\UpdateServerRoomVisitorRequest;
use App\Http\Resources\ServerRoomVisitor\ServerRoomVisitorResource;
use App\Models\ServerRoomVisitor;
use App\Services\Ops\ServerRoomVisitorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServerRoomVisitorController extends Controller
{
    public function __construct(
        private readonly ServerRoomVisitorService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $visitors = $this->service->getAll(
            filters: $request->only(['status', 'from', 'to', 'search']),
            perPage: $request->integer('per_page', 15)
        );

        return ApiResponse::paginated(
            $visitors,
            ServerRoomVisitorResource::collection($visitors),
            'Server room visitors retrieved successfully.'
        );
    }

    public function store(StoreServerRoomVisitorRequest $request): JsonResponse
    {
        $visitor = $this->service->store($request->validated());

        return ApiResponse::success(
            new ServerRoomVisitorResource($visitor),
            'Server room visitor logged successfully.',
            201
        );
    }

    public function show(ServerRoomVisitor $visitor): JsonResponse
    {
        return ApiResponse::success(
            new ServerRoomVisitorResource($visitor->load(['escort', 'creator'])),
            'Server room visitor retrieved successfully.'
        );
    }

    public function update(UpdateServerRoomVisitorRequest $request, ServerRoomVisitor $visitor): JsonResponse
    {
        $visitor = $this->service->update($visitor, $request->validated());

        return ApiResponse::success(
            new ServerRoomVisitorResource($visitor),
            'Server room visitor updated successfully.'
        );
    }

    public function checkout(Request $request, ServerRoomVisitor $visitor): JsonResponse
    {
        $validated = $request->validate([
            'exit_at' => ['required', 'date'],
        ]);

        $visitor = $this->service->checkout($visitor, $validated['exit_at']);

        return ApiResponse::success(
            new ServerRoomVisitorResource($visitor),
            'Visitor checked out successfully.'
        );
    }

    public function destroy(ServerRoomVisitor $visitor): JsonResponse
    {
        $this->service->delete($visitor);

        return ApiResponse::success(null, 'Server room visitor deleted successfully.');
    }
}
