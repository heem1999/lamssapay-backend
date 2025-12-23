<?php

namespace App\Listeners;

use App\Events\TransactionAuthorized;
use App\Events\TransactionDeclined;
use App\Services\Ledger\LedgerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogTransactionToLedger implements ShouldQueue
{
    use InteractsWithQueue;

    protected $ledgerService;

    /**
     * Create the event listener.
     */
    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * Handle the event.
     */
    public function handle(TransactionAuthorized|TransactionDeclined $event): void
    {
        $requestData = $event->requestData;
        $authResult = $event->authResult;

        $ledgerData = [
            'transaction_id' => $authResult['transaction_id'],
            'device_id' => $requestData['device_id'] ?? null,
            'card_token' => $requestData['card_token'],
            'merchant_id' => $requestData['merchant_name'] ?? null, // Mapping name to ID for MVP
            'amount' => $requestData['amount'],
            'currency' => $requestData['currency'],
            'status' => $authResult['status'],
            'auth_code' => $authResult['auth_code'],
        ];

        $this->ledgerService->recordEntry($ledgerData);
    }
}
