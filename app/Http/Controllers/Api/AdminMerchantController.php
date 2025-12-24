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
        $requests = MerchantRequest::where('status', 'pending')->latest()->get();

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

        // We need to update approveRequest to handle null reviewer if we want to be strict,
        // or we can just update the status directly here for MVP simplicity if the service requires a User.
        
        // Let's update the request directly for MVP to avoid dependency on User model for Admin
        $merchantRequest->update([
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        // We might also want to create the Merchant record here if needed, 
        // but for Phase 10 MVP, just flipping the status might be enough to "enable" the mode on the client.
        
        return response()->json([
            'message' => 'Merchant request approved.',
            'data' => $merchantRequest,
        ]);
    }

    /**
     * Reject a merchant request.
     */
    public function reject(Request $request, $id): JsonResponse
    {
        $merchantRequest = MerchantRequest::findOrFail($id);

        $merchantRequest->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Merchant request rejected.',
            'data' => $merchantRequest,
        ]);
    }
}
