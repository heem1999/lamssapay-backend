<?php

namespace App\Services\Payment;

use App\Models\Transaction;

class FeeCalculationService
{
    public function calculateFee(float $amount, string $type): float
    {
        // Basic fee logic for now. 
        // In a real app, this would query the 'fees' table based on type, user tier, etc.
        
        if ($type === 'transfer') {
            return 0.00; // Free P2P transfers for now
        }

        if ($type === 'payment') {
            return $amount * 0.029 + 0.30; // Standard 2.9% + 30c
        }

        return 0.00;
    }
}
