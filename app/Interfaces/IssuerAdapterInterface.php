<?php

namespace App\Interfaces;

interface IssuerAdapterInterface
{
    /**
     * Initiate verification for a card.
     * Returns an array with reference ID and masked phone number.
     *
     * @param array $cardData
     * @return array ['reference' => string, 'masked_phone' => string]
     */
    public function initiateVerification(array $cardData): array;

    /**
     * Validate the OTP provided by the user.
     *
     * @param string $reference
     * @param string $otp
     * @return bool
     */
    public function validateOtp(string $reference, string $otp): bool;
}
