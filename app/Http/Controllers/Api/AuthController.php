<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'User registered successfully.',
            'data' => $user,
        ], 201);
    }

    /**
     * Login user.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->email,
            $request->password,
            $request->device_name
        );

        if ($result['two_factor_required']) {
            return response()->json([
                'message' => 'Two-factor authentication required.',
                'two_factor_required' => true,
                'user_id' => $result['user_id'],
            ]);
        }

        return response()->json([
            'message' => 'Login successful.',
            'data' => $result,
        ]);
    }

    /**
     * Verify 2FA code for login.
     */
    public function verifyTwoFactor(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'code' => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        $user = User::findOrFail($request->user_id);
        
        $result = $this->authService->verifyTwoFactorLogin(
            $user,
            $request->code,
            $request->device_name
        );

        return response()->json([
            'message' => 'Two-factor authentication successful.',
            'data' => $result,
        ]);
    }

    /**
     * Logout user.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Get authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user(),
        ]);
    }

    /**
     * Enable 2FA.
     */
    public function enableTwoFactor(Request $request): JsonResponse
    {
        $data = $this->authService->enableTwoFactor($request->user());

        return response()->json([
            'message' => 'Scan the QR code to enable 2FA.',
            'data' => $data,
        ]);
    }

    /**
     * Confirm 2FA setup.
     */
    public function confirmTwoFactor(Request $request): JsonResponse
    {
        $request->validate([
            'secret' => 'required|string',
            'code' => 'required|string',
        ]);

        $this->authService->confirmTwoFactor(
            $request->user(),
            $request->secret,
            $request->code
        );

        return response()->json([
            'message' => 'Two-factor authentication enabled successfully.',
        ]);
    }

    /**
     * Disable 2FA.
     */
    public function disableTwoFactor(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $this->authService->disableTwoFactor($request->user());

        return response()->json([
            'message' => 'Two-factor authentication disabled successfully.',
        ]);
    }
}
