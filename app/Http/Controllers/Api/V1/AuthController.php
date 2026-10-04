<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\StaffResource;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Exchange staff email and password for a Sanctum token.
     */
    public function login(LoginRequest $request, TenantContext $tenants): JsonResponse
    {
        $tenant = $tenants->get();
        $user = User::query()->where('email', $request->string('email')->toString())->first();

        if ($user === null || $tenant === null || $user->tenant_id !== $tenant->id || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => StaffResource::make($user),
        ]);
    }

    /**
     * Return the authenticated staff member.
     */
    public function me(Request $request): StaffResource
    {
        return StaffResource::make($request->user());
    }

    /**
     * Revoke the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }
}
