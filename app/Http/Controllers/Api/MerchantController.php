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
     * Request merchant access (Phase 10 MVP).
     */
    public function requestAccess(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string',
            'settlement_card_token' => 'required|string',
        ]);

        // Find the card
        $card = \App\Models\Card::where('token_reference', $request->settlement_card_token)
            ->where('device_id', $request->device_id) // Ensure card belongs to device
            ->first();

        if (!$card) {
            return response()->json(['message' => 'Card not found.'], 404);
        }

        // Check if card is already pending or approved
        if (in_array($card->merchant_status, ['MERCHANT_PENDING', 'MERCHANT_APPROVED'])) {
            return response()->json([
                'message' => 'Card is already ' . strtolower(str_replace('MERCHANT_', '', $card->merchant_status)) . '.',
            ], 409);
        }

        // Create Request
        try {
            $merchantRequest = $this->merchantService->submitRequest(null, $request->all());

            // Update Card Status
            $card->merchant_status = 'MERCHANT_PENDING';
            $card->save();

            return response()->json([
                'message' => 'Merchant access requested successfully.',
                'data' => $merchantRequest,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to process request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check status of merchant requests for a device.
     */
    public function checkStatus(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string',
        ]);

        $requests = \App\Models\MerchantRequest::where('device_id', $request->device_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $requests
        ]);
    }

    /**
     * Cancel a merchant request.
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string',
        ]);

        $merchantRequest = \App\Models\MerchantRequest::find($id);

        if (!$merchantRequest) {
            return response()->json(['message' => 'Request not found.'], 404);
        }

        if ($merchantRequest->device_id !== $request->device_id) {
             return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if (in_array($merchantRequest->status, ['cancelled', 'rejected'])) {
             return response()->json(['message' => 'Request is already ' . $merchantRequest->status . '.'], 400);
        }

        // Update Request Status
        $merchantRequest->status = 'cancelled';
        $merchantRequest->save();

        // Update Card Status
        $card = \App\Models\Card::where('token_reference', $merchantRequest->settlement_card_token)->first();
        if ($card) {
            $card->merchant_status = 'CONSUMER_ONLY';
            $card->save();
        }

        return response()->json([
            'message' => 'Request cancelled successfully.',
            'data' => $merchantRequest
        ]);
    }

    /**
     * Disable merchant mode for a card.
     */
    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string',
            'card_token' => 'required|string',
        ]);

        $card = \App\Models\Card::where('token_reference', $request->card_token)
            ->where('device_id', $request->device_id)
            ->first();

        if (!$card) {
            return response()->json(['message' => 'Card not found.'], 404);
        }

        if ($card->merchant_status !== 'MERCHANT_APPROVED') {
            return response()->json(['message' => 'Card is not approved for merchant use.'], 400);
        }

        $card->merchant_status = 'MERCHANT_DISABLED';
        $card->is_settlement_default = false;
        $card->save();

        return response()->json(['message' => 'Merchant mode disabled for this card.']);
    }

    /**
     * Set a card as the default settlement card.
     */
    public function setDefault(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string',
            'card_token' => 'required|string',
        ]);

        $card = \App\Models\Card::where('token_reference', $request->card_token)
            ->where('device_id', $request->device_id)
            ->first();

        if (!$card) {
            return response()->json(['message' => 'Card not found.'], 404);
        }

        if ($card->merchant_status !== 'MERCHANT_APPROVED') {
            return response()->json(['message' => 'Card must be approved to be set as default.'], 400);
        }

        // Unset other defaults for this device
        \App\Models\Card::where('device_id', $request->device_id)
            ->update(['is_settlement_default' => false]);

        $card->is_settlement_default = true;
        $card->save();

        return response()->json(['message' => 'Default settlement card updated.']);
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

        if (!$merchant) {
            return response()->json(['message' => 'User is not a merchant.'], 404);
        }

        // Expose API keys only to the owner
        return response()->json([
            'data' => $merchant->makeVisible(['api_key_live', 'api_key_test']),
        ]);
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
