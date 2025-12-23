<?php

namespace App\Services\Payment;

use App\Events\TransactionAuthorized;
use App\Events\TransactionDeclined;
use Illuminate\Support\Str;

class PaymentAuthorizationService
{
    /**
     * Authorize a payment transaction.
     *
     * @param array $data
     * @return array
     */
    public function authorize(array $data): array
    {
        // 1. Token Verification
        // In a real system, we would look up the card by the payment token (DPAN).
        // For MVP, we assume the card_token is the card ID or a direct reference.
        
        $cardToken = $data['card_token'];
        $amount = $data['amount'];
        $deviceId = $data['device_id'] ?? 'unknown';

        // Mock Token Verification
        // If token starts with "tok_blocked", we decline
        if (str_starts_with($cardToken, 'tok_blocked')) {
            return $this->decline($data, 'Card is blocked');
        }

        // 2. Risk Checks
        // Rule 1: Amount Limit
        if ($amount > 5000) {
            return $this->decline($data, 'Transaction amount exceeds limit');
        }

        // Rule 2: Device Block (Mock)
        if ($deviceId === 'dev_blocked') {
            return $this->decline($data, 'Device is blocked');
        }

        // 3. Approve
        return $this->approve($data);
    }

    protected function approve(array $data): array
    {
        $authCode = 'AUTH-' . strtoupper(Str::random(6));
        $transactionId = 'TXN-' . strtoupper(Str::random(12));
        
        $result = [
            'status' => 'APPROVED',
            'auth_code' => $authCode,
            'transaction_id' => $transactionId,
            'message' => 'Transaction approved',
            'timestamp' => now()->toIso8601String(),
        ];

        event(new TransactionAuthorized($data, $result));

        return $result;
    }

    protected function decline(array $data, string $reason): array
    {
        $transactionId = 'TXN-' . strtoupper(Str::random(12));

        $result = [
            'status' => 'DECLINED',
            'auth_code' => null,
            'transaction_id' => $transactionId,
            'message' => $reason,
            'timestamp' => now()->toIso8601String(),
        ];

        event(new TransactionDeclined($data, $result));

        return $result;
    }
}
