<?php

namespace App\Interfaces;

interface TokenProviderInterface
{
    /**
     * Tokenize sensitive card data.
     *
     * @param array $cardData ['pan', 'cvv', 'expiry_month', 'expiry_year', 'holder_name']
     * @param string $deviceId
     * @return string The secure token
     */
    public function tokenize(array $cardData, string $deviceId): string;

    /**
     * Detokenize or delete a token (if supported/needed).
     *
     * @param string $token
     * @return bool
     */
    public function deleteToken(string $token): bool;
}
