<?php

namespace App\Services\Adapters;

use App\Interfaces\IssuerAdapterInterface;
use Illuminate\Support\Facades\Log;

class MockIssuerAdapter implements IssuerAdapterInterface
{
    public function initiateVerification(array $cardData): array
    {
        // Simulate calling the bank API to start verification
        $reference = 'REQ_' . uniqid();
        
        // In a real app, this would trigger an SMS via the bank.
        // Here we just log it.
        Log::info("MockIssuer: Verification initiated for card ending in " . substr($cardData['pan'], -4));
        Log::info("MockIssuer: OTP is 1234 (Reference: $reference)");

        // Return reference and a mock masked phone number
        return [
            'reference' => $reference,
            'masked_phone' => '*******123'
        ];
    }

    public function validateOtp(string $reference, string $otp): bool
    {
        // Simulate validating the OTP with the bank
        Log::info("MockIssuer: Validating OTP $otp for reference $reference");

        // Hardcoded OTP for testing
        return $otp === '1234';
    }
}
