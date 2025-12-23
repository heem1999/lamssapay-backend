<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\CreateTransferRequest;
use App\Models\User;
use App\Services\Payment\PaymentAuthorizationService;
use App\Services\Payment\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    protected $transactionService;
    protected $authService;

    public function __construct(TransactionService $transactionService, PaymentAuthorizationService $authService)
    {
        $this->transactionService = $transactionService;
        $this->authService = $authService;
    }

    /**
     * Get transaction history.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $transactions = $user->transactions()->latest()->paginate(20);

        return response()->json($transactions);
    }

    /**
     * Get transaction details.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $transaction = $user->transactions()->findOrFail($id);

        return response()->json([
            'data' => $transaction,
        ]);
    }

    /**
     * Initiate a P2P transfer.
     */
    public function transfer(CreateTransferRequest $request): JsonResponse
    {
        $receiver = User::where('email', $request->email)->firstOrFail();

        // Prevent self-transfer
        if ($receiver->id === $request->user()->id) {
            return response()->json(['message' => 'Cannot transfer to yourself.'], 422);
        }

        try {
            $transaction = $this->transactionService->transfer(
                $request->user(),
                $receiver,
                $request->amount,
                $request->currency,
                $request->description
            );

            return response()->json([
                'message' => 'Transfer successful.',
                'data' => $transaction,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Authorize a Tap-to-Pay transaction (Phase 7).
     * This simulates the Issuer authorizing a transaction from a POS/Network.
     */
    public function authorizePayment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'card_token' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'merchant_name' => 'nullable|string',
            'device_id' => 'nullable|string',
            'cryptogram' => 'nullable|string',
            'nfc_type' => 'nullable|string',
        ]);

        $result = $this->authService->authorize($data);
        
        return response()->json($result);
    }
}
