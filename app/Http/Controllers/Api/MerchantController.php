<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\RegisterMerchantRequest;
use App\Services\Merchant\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MerchantController extends Controller
{
    protected $merchantService;

    public function __construct(MerchantService $merchantService)
    {
        $this->merchantService = $merchantService;
    }

    /**
     * Submit a merchant application.
     */
    public function register(RegisterMerchantRequest $request): JsonResponse
    {
        // Check if user already has a pending request or is a merchant
        if ($request->user()->merchantRequests()->whereIn('status', ['pending', 'approved'])->exists()) {
            return response()->json(['message' => 'You already have a pending or approved merchant request.'], 409);
        }

        $merchantRequest = $this->merchantService->submitRequest($request->user(), $request->validated());

        return response()->json([
            'message' => 'Merchant application submitted successfully.',
            'data' => $merchantRequest,
        ], 201);
    }

    /**
     * Get current user's merchant profile.
     */
    public function me(Request $request): JsonResponse
    {
        $merchant = $request->user()->merchant;

        if ($merchant) {
            $data = $merchant->makeVisible(['api_key_live', 'api_key_test'])->toArray();
            $data['status'] = 'approved';
            return response()->json([
                'data' => $data,
            ]);
        }

        // Check for latest request status
        $latestRequest = $request->user()->merchantRequests()->latest()->first();

        if ($latestRequest) {
            return response()->json([
                'data' => $latestRequest,
            ]);
        }

        return response()->json(['message' => 'User is not a merchant.'], 404);
    }

    /**
     * Rotate API Keys.
     */
    public function rotateKeys(Request $request): JsonResponse
    {
        $request->validate([
            'environment' => 'required|in:live,test',
        ]);

        $merchant = $request->user()->merchant;

        if (!$merchant) {
            return response()->json(['message' => 'User is not a merchant.'], 404);
        }

        $merchant = $this->merchantService->rotateApiKeys($merchant, $request->environment);

        return response()->json([
            'message' => 'API keys rotated successfully.',
            'data' => $merchant->makeVisible(['api_key_live', 'api_key_test']),
        ]);
    }
}
