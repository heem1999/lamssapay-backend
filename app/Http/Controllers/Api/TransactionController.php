<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\CreateTransferRequest;
use App\Models\User;
use App\Services\Payment\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use App\Http\Requests\Transaction\ProcessPaymentRequest;

class TransactionController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Process a Tap-to-Pay payment.
     */
    public function payment(ProcessPaymentRequest $request): JsonResponse
    {
        try {
            $transaction = $this->transactionService->processPayment(
                $request->user(),
                $request->card_id,
                $request->amount,
                $request->currency,
                $request->merchant_name,
                $request->cryptogram
            );

            return response()->json([
                'message' => 'Payment successful.',
                'data' => $transaction,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
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
}
