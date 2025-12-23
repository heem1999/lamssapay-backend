<?php

namespace App\Services\Payment;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionService
{
    protected $walletService;
    protected $feeService;

    public function __construct(WalletService $walletService, FeeCalculationService $feeService)
    {
        $this->walletService = $walletService;
        $this->feeService = $feeService;
    }

    /**
     * Process a P2P transfer between users.
     */
    public function transfer(User $sender, User $receiver, float $amount, string $currency = 'USD', ?string $description = null): Transaction
    {
        return DB::transaction(function () use ($sender, $receiver, $amount, $currency, $description) {
            $senderWallet = $this->walletService->getWallet($sender, $currency);
            $receiverWallet = $this->walletService->getWallet($receiver, $currency);

            if (!$senderWallet) {
                throw new \Exception("Sender does not have a {$currency} wallet.");
            }

            // Auto-create receiver wallet if it doesn't exist
            if (!$receiverWallet) {
                $receiverWallet = $this->walletService->createWallet($receiver, $currency);
            }

            $fee = $this->feeService->calculateFee($amount, 'transfer');
            $totalAmount = $amount + $fee;

            // Debit Sender
            $this->walletService->debit($senderWallet, $totalAmount);

            // Credit Receiver
            $this->walletService->credit($receiverWallet, $amount);

            $reference = 'TRX-' . strtoupper(Str::random(10));

            // Create Sender Transaction Record
            $transaction = Transaction::create([
                'transaction_reference' => $reference,
                'user_id' => $sender->id,
                'wallet_id' => $senderWallet->id,
                'type' => 'transfer',
                'amount' => -$amount, // Negative for debit
                'currency' => $currency,
                'fee' => $fee,
                'total_amount' => -$totalAmount,
                'status' => 'completed',
                'description' => $description ?? 'Transfer to ' . $receiver->email,
                'metadata' => ['counterparty_id' => $receiver->id],
                'processed_at' => now(),
            ]);

            // Create Receiver Transaction Record
            Transaction::create([
                'transaction_reference' => $reference,
                'user_id' => $receiver->id,
                'wallet_id' => $receiverWallet->id,
                'type' => 'transfer',
                'amount' => $amount, // Positive for credit
                'currency' => $currency,
                'fee' => 0, // Usually receiver doesn't pay fee for P2P, or split
                'total_amount' => $amount,
                'status' => 'completed',
                'description' => 'Received from ' . $sender->email,
                'metadata' => ['counterparty_id' => $sender->id],
                'processed_at' => now(),
            ]);

            return $transaction;
        });
    }

    /**
     * Process a Tap-to-Pay payment.
     */
    public function processPayment(User $user, int $cardId, float $amount, string $currency, string $merchantName, string $cryptogram): Transaction
    {
        return DB::transaction(function () use ($user, $cardId, $amount, $currency, $merchantName, $cryptogram) {
            // 1. Verify Cryptogram (Mock)
            if (empty($cryptogram)) {
                throw new \Exception("Invalid payment cryptogram.");
            }

            // 2. Get Wallet
            $wallet = $this->walletService->getWallet($user, $currency);
            if (!$wallet) {
                throw new \Exception("User does not have a {$currency} wallet.");
            }

            // 3. Calculate Fee (e.g., 1% for payments)
            $fee = $this->feeService->calculateFee($amount, 'payment');
            $totalAmount = $amount + $fee;

            // 4. Debit User
            $this->walletService->debit($wallet, $totalAmount);

            // 5. Create Transaction Record
            $reference = 'PAY-' . strtoupper(Str::random(10));

            $transaction = Transaction::create([
                'transaction_reference' => $reference,
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => 'payment',
                'amount' => -$amount,
                'currency' => $currency,
                'fee' => $fee,
                'total_amount' => -$totalAmount,
                'status' => 'completed',
                'description' => "Payment to $merchantName",
                'metadata' => [
                    'merchant_name' => $merchantName,
                    'card_id' => $cardId,
                    'cryptogram_fragment' => substr($cryptogram, 0, 8) . '...'
                ],
                'processed_at' => now(),
            ]);

            return $transaction;
        });
    }
}
