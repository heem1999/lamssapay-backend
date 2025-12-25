<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MerchantRequest;
use App\Services\Merchant\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMerchantController extends Controller
{
    protected $merchantService;

    public function __construct(MerchantService $merchantService)
    {
        $this->merchantService = $merchantService;
    }

    /**
     * List all pending merchant requests.
     */
    public function index(): JsonResponse
    {
        $requests = MerchantRequest::with('card')->where('status', 'pending')->latest()->get();

        return response()->json([
            'data' => $requests,
        ]);
    }

    /**
     * Approve a merchant request.
     */
    public function approve(Request $request, $id): JsonResponse
    {
        $merchantRequest = MerchantRequest::findOrFail($id);

        // In MVP, we don't have a logged-in admin user, so we pass null or a mock user
        // In production, this would be $request->user()
        $reviewer = null; 

        // Update Request Status
        $merchantRequest->update([
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        // Update Card Status
        $card = \App\Models\Card::where('token_reference', $merchantRequest->settlement_card_token)->first();
        if ($card) {
            $card->merchant_status = 'MERCHANT_APPROVED';
            
            // If this is the first approved card for the device, make it default
            $hasDefault = \App\Models\Card::where('device_id', $card->device_id)
                ->where('is_settlement_default', true)
                ->exists();
            
            if (!$hasDefault) {
                $card->is_settlement_default = true;
            }
            
            $card->save();
        }
        
        return response()->json([
            'message' => 'Merchant request approved.',
            'data' => $merchantRequest,
        ]);
    }

    /**     * Approve a merchant request by card token (Dev/MVP Helper).
     */
    public function approveByCard(Request $request): JsonResponse
    {
        $request->validate([
            'card_token' => 'required|string',
        ]);

        $merchantRequest = MerchantRequest::where('settlement_card_token', $request->card_token)
            ->where('status', 'pending')
            ->first();

        if (!$merchantRequest) {
            return response()->json(['message' => 'No pending request found for this card.'], 404);
        }

        return $this->approve($request, $merchantRequest->id);
    }

    /**     * Reject a merchant request.
     */
    public function reject(Request $request, $id): JsonResponse
    {
        $merchantRequest = MerchantRequest::findOrFail($id);

        $merchantRequest->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
        ]);

        // Update Card Status
        $card = \App\Models\Card::where('token_reference', $merchantRequest->settlement_card_token)->first();
        if ($card) {
            $card->merchant_status = 'CONSUMER_ONLY'; // Revert to consumer only
            $card->save();
        }

        return response()->json([
            'message' => 'Merchant request rejected.',
            'data' => $merchantRequest,
        ]);
    }
}
