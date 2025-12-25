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
        // Find the device first to get the internal ID
        $device = \App\Models\Device::where('device_id', $deviceId)->first();

        if (!$device) {
            return response()->json([
                'status' => 'DECLINED',
                'message' => 'Device not registered.'
            ], 403);
        }

        // Find the default settlement card for this device
        $settlementCard = \App\Models\Card::where('device_id', $device->id)
            ->where('merchant_status', 'MERCHANT_APPROVED')
            ->where('is_settlement_default', true)
            ->first();

        if (!$settlementCard) {
            return response()->json([
                'status' => 'DECLINED',
                'message' => 'No active settlement card found. Please set a default card for merchant payments.'
            ], 403);
        }

        // Get Merchant Info (Optional: from MerchantRequest if needed for business name)
        $merchantRequest = MerchantRequest::where('settlement_card_token', $settlementCard->token_reference)
             ->where('status', 'approved')
             ->first();

        $merchantName = $merchantRequest->business_name ?? 'Merchant Device ' . substr($deviceId, 0, 6);
        $merchantId = $merchantRequest->id ?? 0;

        // 3. Process Payment (Issuer Check)
        // We treat the nfc_payload as the card_token for the MVP
        $paymentData = [
            'card_token' => $request->nfc_payload,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'merchant_name' => $merchantName,
            'device_id' => $deviceId, // The merchant's device
            'merchant_id' => $merchantId,
        ];

        $result = $this->paymentService->authorize($paymentData);

        // 4. Ledger Recording (Mock for MVP)
        if ($result['status'] === 'APPROVED') {
            $this->recordLedger($settlementCard, $merchantId, $request->nfc_payload, $request->amount, $request->currency, $result['transaction_id']);
        }

        return response()->json($result);
    }

    /**
     * Record the transaction in the ledger.
     */
    protected function recordLedger($settlementCard, $merchantId, $customerCardToken, $amount, $currency, $transactionId)
    {
        // 1. Consumer Entry (DEBIT) - Handled by TransactionAuthorized event listener
        // We do not need to record it here to avoid duplicate entry error.

        // 2. Merchant Entry (CREDIT)
        \App\Models\LedgerEntry::create([
            'ledger_id' => Str::uuid()->toString(),
            'transaction_id' => $transactionId . '-M', // Append suffix to avoid unique constraint violation on transaction_id if it's unique in DB
            'device_id' => request()->device_id,
            'card_token' => $settlementCard->token_reference,
            'merchant_id' => $merchantId,
            'amount' => $amount,
            'currency' => $currency,
            'direction' => 'CREDIT',
            'status' => 'APPROVED',
            'auth_code' => strtoupper(uniqid('AUTH')),
        ]);

        Log::info("Ledger Recorded: $transactionId");
    }
}
