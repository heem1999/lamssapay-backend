<?php

namespace App\Services\Wallet;

use App\Models\Card;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class CardVaultService
{
    /**
     * Store a new card securely.
     */
    public function storeCard(User $user, array $data): Card
    {
        // In a real app, we would tokenize with a payment gateway (Stripe, etc.)
        // Here we simulate vaulting by encrypting sensitive data.

        // Ensure user has a wallet
        $wallet = $user->wallets()->firstOrCreate([
            'currency' => 'USD',
        ], [
            'balance' => 0,
            'is_active' => true,
        ]);

        return Card::create([
            'wallet_id' => $wallet->id,
            'card_token' => Crypt::encryptString($data['pan']),
            'token_reference' => 'tok_' . Str::random(24),
            'card_last_four' => substr($data['pan'], -4),
            'card_first_six' => substr($data['pan'], 0, 6),
            'card_brand' => 'visa', // Simplified detection
            'card_type' => $this->detectCardType($data['pan']),
            'holder_name' => Crypt::encryptString($data['card_holder_name']),
            'expiry_month' => Crypt::encryptString($data['expiry_month']),
            'expiry_year' => Crypt::encryptString($data['expiry_year']),
            'billing_address' => isset($data['billing_address']) ? $data['billing_address'] : null,
            'is_default' => $data['is_default'] ?? false,
            'is_active' => true,
        ]);
    }

    /**
     * Detect card type (simplified).
     */
    protected function detectCardType(string $pan): string
    {
        if (str_starts_with($pan, '4')) return 'credit'; // Visa
        if (str_starts_with($pan, '5')) return 'debit'; // Mastercard
        return 'credit';
    }

    /**
     * Delete a card.
     */
    public function deleteCard(Card $card): void
    {
        $card->delete();
    }
}
