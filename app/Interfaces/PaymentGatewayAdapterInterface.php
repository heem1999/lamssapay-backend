<?php

namespace App\Interfaces;

interface PaymentGatewayAdapterInterface
{
    /**
     * Tokenize sensitive card data.
     * Returns a secure token reference from the provider.
     *
     * @param array $cardDetails ['pan', 'cvv', 'expiry_month', 'expiry_year', 'holder_name']
     * @return string
     */
    public function tokenize(array $cardDetails): string;

    /**
     * Remove a token from the provider.
     *
     * @param string $tokenReference
     * @return bool
     */
    public function deleteToken(string $tokenReference): bool;
}
