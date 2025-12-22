<?php

namespace App\Services\Adapters;

use App\Interfaces\NotificationAdapterInterface;
use Illuminate\Support\Facades\Log;

class MockNotificationAdapter implements NotificationAdapterInterface
{
    public function sendOtp(string $destination, string $otp): bool
    {
        Log::info("NotificationService: Sending OTP [$otp] to [$destination]");
        return true;
    }
}
