<?php

namespace App\Interfaces;

interface IssuerAdapterInterface
{
    /**
     * Initiate verification for a card.
     * Returns a reference ID for the verification process.
     *
     * @param array $cardData
     * @return string
     */
    public function initiateVerification(array $cardData): string;

    /**
     * Validate the OTP provided by the user.
     *
     * @param string $reference
     * @param string $otp
     * @return bool
     */
    public function validateOtp(string $reference, string $otp): bool;
}
