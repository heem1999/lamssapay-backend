<?php

namespace App\Services\Gateways;

use App\Interfaces\PaymentGatewayAdapterInterface;
use Illuminate\Support\Str;

class MockPaymentGatewayAdapter implements PaymentGatewayAdapterInterface
{
    public function tokenize(array $cardDetails): string
    {
        // SIMULATION ONLY: In production, this sends data to Stripe/Checkout.com
        // and receives a token. We NEVER store the PAN here.
        
        // Simulate network latency
        sleep(1);

        // Return a fake token (UUID)
        return 'tok_' . Str::uuid()->toString();
    }

    public function deleteToken(string $tokenReference): bool
    {
        // Simulate deletion at the provider
        return true;
    }
}
