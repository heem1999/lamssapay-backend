<?php

namespace App\Services\Wallet;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\WalletException;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Create a new wallet for a user.
     *
     * @param User $user
     * @param string $currency
     * @return Wallet
     */
    public function createWallet(User $user, string $currency = 'USD'): Wallet
    {
        return $user->wallets()->create([
            'currency' => $currency,
            'balance' => 0,
            'status' => 'active',
            'daily_limit' => 1000.00, // Default limit
            'monthly_limit' => 10000.00,
        ]);
    }

    /**
     * Get a user's wallet by currency.
     *
     * @param User $user
     * @param string $currency
     * @return Wallet|null
     */
    public function getWallet(User $user, string $currency = 'USD'): ?Wallet
    {
        return $user->wallets()->where('currency', $currency)->first();
    }

    /**
     * Credit a wallet.
     *
     * @param Wallet $wallet
     * @param float $amount
     * @param string|null $description
     * @return Wallet
     */
    public function credit(Wallet $wallet, float $amount, ?string $description = null): Wallet
    {
        return DB::transaction(function () use ($wallet, $amount) {
            $wallet->balance += $amount;
            $wallet->save();

            // Log transaction here if needed, or let the TransactionService handle it
            
            return $wallet;
        });
    }

    /**
     * Debit a wallet.
     *
     * @param Wallet $wallet
     * @param float $amount
     * @param string|null $description
     * @return Wallet
     * @throws InsufficientBalanceException
     * @throws WalletException
     */
    public function debit(Wallet $wallet, float $amount, ?string $description = null): Wallet
    {
        return DB::transaction(function () use ($wallet, $amount) {
            if ($wallet->balance < $amount) {
                throw new InsufficientBalanceException();
            }

            // Check limits
            // This is a simplified check. In a real app, we'd sum up today's transactions.
            // $this->checkLimits($wallet, $amount);

            $wallet->balance -= $amount;
            $wallet->save();

            return $wallet;
        });
    }

    /**
     * Check if the wallet has sufficient balance.
     *
     * @param Wallet $wallet
     * @param float $amount
     * @return bool
     */
    public function hasSufficientBalance(Wallet $wallet, float $amount): bool
    {
        return $wallet->balance >= $amount;
    }
}
