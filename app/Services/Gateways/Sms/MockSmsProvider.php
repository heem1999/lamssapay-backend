<?php

namespace App\Services\Gateways\Sms;

use App\Interfaces\SmsProviderInterface;
use Illuminate\Support\Facades\Log;

class MockSmsProvider implements SmsProviderInterface
{
    public function send(string $phoneNumber, string $message): bool
    {
        // MVP: Log the SMS instead of sending it
        Log::info("SMS Provider (Mock): Sending to [$phoneNumber]");
        Log::info("Message Content: $message");
        
        return true;
    }
}
