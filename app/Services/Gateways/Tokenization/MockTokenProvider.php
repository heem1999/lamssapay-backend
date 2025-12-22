<?php

namespace App\Services\Gateways\Tokenization;

use App\Interfaces\TokenProviderInterface;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MockTokenProvider implements TokenProviderInterface
{
    public function tokenize(array $cardData, string $deviceId): string
    {
        // SIMULATION: In a real scenario, this sends data to a Vault (VGS, Stripe, etc.)
        // We NEVER store the PAN locally.
        
        Log::info("TokenizationProvider (Mock): Tokenizing card for device [$deviceId]");
        
        // Simulate network latency
        usleep(500000); // 0.5s

        // Return a fake token bound to the device context conceptually
        return 'tok_' . Str::uuid()->toString();
    }

    public function deleteToken(string $token): bool
    {
        Log::info("TokenizationProvider (Mock): Deleting token [$token]");
        return true;
    }
}
