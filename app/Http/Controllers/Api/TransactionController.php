<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\CreateTransferRequest;
use App\Models\User;
use App\Services\Payment\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
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
        $request->validate([
            'card_token' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'merchant_name' => 'nullable|string',
        ]);

        // In a real system, we would look up the card by the payment token (DPAN).
        // For MVP, we assume the card_token is the card ID or a direct reference.
        
        // Mock Logic:
        // 1. If amount > 5000, DECLINE (Limit exceeded)
        // 2. Otherwise, APPROVE
        
        $status = 'APPROVED';
        $message = 'Transaction approved';
        
        if ($request->amount > 5000) {
            $status = 'DECLINED';
            $message = 'Transaction limit exceeded';
        }

        // Log the transaction (Mocking the creation)
        // In production, this would be linked to the actual Card model
        
        return response()->json([
            'status' => $status,
            'message' => $message,
            'auth_code' => $status === 'APPROVED' ? strtoupper(uniqid('AUTH')) : null,
            'transaction_id' => uniqid('TXN'),
            'amount' => $request->amount,
            'currency' => $request->currency,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
