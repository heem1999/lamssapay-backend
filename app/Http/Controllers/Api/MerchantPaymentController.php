<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MerchantRequest;
use App\Services\Payment\PaymentAuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MerchantPaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentAuthorizationService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Authorize a payment where the device acts as a SoftPOS terminal.
     * Phase 11 Implementation.
     */
    public function authorize(Request $request): JsonResponse
    {
        // 1. Validate Request
        $request->validate([
            'device_id' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'nfc_payload' => 'required|string', // In MVP, this is the customer's card token
        ]);

        $deviceId = $request->device_id;

        // 2. Validate Merchant Status (Acquirer Check)
        $merchant = MerchantRequest::where('device_id', $deviceId)
            ->where('status', 'approved')
            ->first();

        if (!$merchant) {
            return response()->json([
                'status' => 'DECLINED',
                'message' => 'Device is not authorized to accept payments. Merchant status must be APPROVED.'
            ], 403);
        }

        // 3. Process Payment (Issuer Check)
        // We treat the nfc_payload as the card_token for the MVP
        $paymentData = [
            'card_token' => $request->nfc_payload,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'merchant_name' => $merchant->business_name ?? 'Merchant Device ' . substr($deviceId, 0, 6),
            'device_id' => $deviceId, // The merchant's device
            'merchant_id' => $merchant->id,
        ];

        $result = $this->paymentService->authorize($paymentData);

        // 4. Ledger Recording (Mock for MVP)
        if ($result['status'] === 'APPROVED') {
            $this->recordLedger($merchant, $request->nfc_payload, $request->amount, $request->currency, $result['transaction_id']);
        }

        return response()->json($result);
    }

    /**
     * Record the transaction in the ledger.
     */
    protected function recordLedger($merchant, $customerCardToken, $amount, $currency, $transactionId)
    {
        // 1. Consumer Entry (DEBIT)
        \App\Models\LedgerEntry::create([
            'ledger_id' => Str::uuid()->toString(),
            'transaction_id' => $transactionId,
            'device_id' => request()->device_id, // The merchant device initiated it, but we track the card
            'card_token' => $customerCardToken,
            'merchant_id' => $merchant->id,
            'amount' => $amount,
            'currency' => $currency,
            'direction' => 'DEBIT',
            'status' => 'APPROVED',
            'auth_code' => strtoupper(uniqid('AUTH')),
        ]);

        // 2. Merchant Entry (CREDIT)
        \App\Models\LedgerEntry::create([
            'ledger_id' => Str::uuid()->toString(),
            'transaction_id' => $transactionId . '-M', // Append suffix to avoid unique constraint violation on transaction_id if it's unique in DB
            'device_id' => request()->device_id,
            'card_token' => $merchant->settlement_card_token,
            'merchant_id' => $merchant->id,
            'amount' => $amount,
            'currency' => $currency,
            'direction' => 'CREDIT',
            'status' => 'APPROVED',
            'auth_code' => strtoupper(uniqid('AUTH')),
        ]);

        Log::info("Ledger Recorded: $transactionId");
    }
}
