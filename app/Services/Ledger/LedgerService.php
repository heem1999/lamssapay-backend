<?php

namespace App\Services\Ledger;

use App\Models\LedgerEntry;
use Illuminate\Support\Str;
use Exception;

class LedgerService
{
    /**
     * Record a transaction in the ledger.
     * This is an append-only operation.
     *
     * @param array $data
     * @return LedgerEntry
     * @throws Exception
     */
    public function recordEntry(array $data): LedgerEntry
    {
        // Idempotency Check
        if (LedgerEntry::where('transaction_id', $data['transaction_id'])->exists()) {
            // In a real system, we might return the existing entry or throw a specific IdempotencyException
            // For now, we'll just return the existing one to be safe.
            return LedgerEntry::where('transaction_id', $data['transaction_id'])->first();
        }

        return LedgerEntry::create([
            'ledger_id' => (string) Str::uuid(),
            'transaction_id' => $data['transaction_id'],
            'device_id' => $data['device_id'] ?? null,
            'card_token' => $data['card_token'],
            'merchant_id' => $data['merchant_id'] ?? null,
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'direction' => 'DEBIT', // Phase 9 says "DEBIT" for these transactions
            'status' => $data['status'],
            'auth_code' => $data['auth_code'] ?? null,
        ]);
    }
}
