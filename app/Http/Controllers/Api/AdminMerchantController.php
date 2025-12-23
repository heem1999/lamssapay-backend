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
        $requests = MerchantRequest::where('status', 'pending')->with('user')->latest()->paginate(20);
        return response()->json($requests);
    }

    /**
     * Approve a merchant request.
     */
    public function approve(Request $request, $id): JsonResponse
    {
        $merchantRequest = MerchantRequest::findOrFail($id);

        if ($merchantRequest->status !== 'pending') {
            return response()->json(['message' => 'Request is not pending.'], 400);
        }

        $merchant = $this->merchantService->approveRequest($merchantRequest, $request->user());

        return response()->json([
            'message' => 'Merchant approved successfully.',
            'data' => $merchant,
        ]);
    }

    /**
     * Reject a merchant request.
     */
    public function reject(Request $request, $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string',
        ]);

        $merchantRequest = MerchantRequest::findOrFail($id);

        if ($merchantRequest->status !== 'pending') {
            return response()->json(['message' => 'Request is not pending.'], 400);
        }

        $merchantRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Merchant request rejected.',
            'data' => $merchantRequest,
        ]);
    }
}
