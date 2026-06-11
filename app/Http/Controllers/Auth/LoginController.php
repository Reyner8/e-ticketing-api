<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): array
    {
        $request->authenticate();

        $user = $request->user();

        $user->update([
            'last_login' => now(),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' =>   new UserResource($user),
            'token' => $token
        ];
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();

        return ApiResponse::success(
            null,
            'Logout success'
        );
    }
}
