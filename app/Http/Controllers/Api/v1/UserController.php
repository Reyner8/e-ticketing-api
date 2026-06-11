<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserPreferenceRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\User\UserResource;
use App\Http\Resources\User\UserResourceDetail;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $users = $this->service->getAll(
            filters: $request->only(['role', 'team', 'is_active', 'search']),
            perPage: $request->integer('per_page', 15)
        );

        return ApiResponse::paginated(
            $users,
            UserResource::collection($users),
            'User data retrieved successfully.'
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->service->store($request->validated());

        return ApiResponse::success(
            new UserResourceDetail($user),
            'User added successfully.',
            201
        );
    }

    public function show(User $user): JsonResponse
    {
        return ApiResponse::success(
            new UserResourceDetail($user),
            'User data retrieved successfully.'
        );
    }

    public function me(): JsonResponse
    {
        return ApiResponse::success(
            new UserResourceDetail(Auth::user()),
            'Profile retrieved successfully.'
        );
    }

    public function update(User $user, UpdateUserRequest $request): JsonResponse
    {
        $updated = $this->service->update($user, $request->validated());

        return ApiResponse::success(
            new UserResourceDetail($updated),
            'User updated successfully.'
        );
    }

    public function updatePreferences(User $user, UpdateUserPreferenceRequest $request): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if ($user->id !== $authUser->id && ! $authUser->isAdmin()) {
            abort(403, 'You are not authorized to update this user\'s preferences');
        }

        $updated = $this->service->updatePreferences($user, $request->validated());

        return ApiResponse::success(
            new UserResourceDetail($updated),
            'User preferences updated successfully.'
        );
    }

    public function updateMyPreferences(UpdateUserPreferenceRequest $request): JsonResponse
    {
        $user = Auth::user();
        $updated = $this->service->updatePreferences($user, $request->validated());

        return ApiResponse::success(
            new UserResourceDetail($updated),
            'Preferences updated successfully.'
        );
    }

    public function toggleActive(User $user): JsonResponse
    {
        $updated = $this->service->toggleActive($user);
        $status = $updated->is_active ? 'activated' : 'deactivated';

        return ApiResponse::success(
            new UserResourceDetail($updated),
            "User {$status} successfully."
        );
    }

    public function destroy(User $user): JsonResponse 
    {
        $this->service->delete($user);
        
        return ApiResponse::success(
            null,
            'User deleted successfully'
        );
    }
}
