<?php

namespace App\Services;

use App\Interfaces\SmsProviderInterface;

class NotificationService
{
    protected $smsProvider;

    public function __construct(SmsProviderInterface $smsProvider)
    {
        $this->smsProvider = $smsProvider;
    }

    /**
     * Send an OTP message.
     *
     * @param string $phoneNumber
     * @param string $otp
     * @return bool
     */
    public function sendOtp(string $phoneNumber, string $otp): bool
    {
        // Template logic goes here
        $message = "Your LamssaPay verification code is: {$otp}. Do not share this code with anyone.";
        
        return $this->smsProvider->send($phoneNumber, $message);
    }

    /**
     * Send a welcome message.
     *
     * @param string $phoneNumber
     * @return bool
     */
    public function sendWelcomeMessage(string $phoneNumber): bool
    {
        $message = "Welcome to LamssaPay! Your wallet is ready.";
        return $this->smsProvider->send($phoneNumber, $message);
    }
}
