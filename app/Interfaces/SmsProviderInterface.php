<?php

namespace App\Interfaces;

interface SmsProviderInterface
{
    /**
     * Send an SMS to a specific phone number.
     *
     * @param string $phoneNumber
     * @param string $message
     * @return bool
     */
    public function send(string $phoneNumber, string $message): bool;
}
