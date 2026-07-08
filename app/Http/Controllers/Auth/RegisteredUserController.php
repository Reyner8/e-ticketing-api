<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Http\Resources\User\UserResourceDetail;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(RegisterUserRequest $request): JsonResponse
    {
        $user = User::create([
            ...$request->validated(),
            'role' => UserRole::Reporter->value,
            'is_active' => true,
        ]);

        return ApiResponse::success(
            new UserResourceDetail($user),
            'User created successfully'
        );
    }
}
