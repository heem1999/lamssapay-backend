<?php

namespace App\Services;

use App\Interfaces\TokenProviderInterface;

class TokenizationService
{
    protected $provider;

    public function __construct(TokenProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Securely tokenize card data.
     *
     * @param array $data
     * @param string $deviceId
     * @return string
     */
    public function createToken(array $data, string $deviceId): string
    {
        return $this->provider->tokenize($data, $deviceId);
    }

    /**
     * Remove a token from the vault.
     *
     * @param string $token
     * @return void
     */
    public function removeToken(string $token): void
    {
        $this->provider->deleteToken($token);
    }
}
