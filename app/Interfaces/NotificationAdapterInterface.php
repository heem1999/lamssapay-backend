<?php

namespace App\Interfaces;

interface NotificationAdapterInterface
{
    /**
     * Send an OTP to a specific destination.
     *
     * @param string $destination (Phone number or reference)
     * @param string $otp
     * @return bool
     */
    public function sendOtp(string $destination, string $otp): bool;
}
