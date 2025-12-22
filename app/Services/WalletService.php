<?php

namespace App\Services;

use App\Interfaces\PaymentGatewayAdapterInterface;
use App\Models\Card;
use App\Models\Device;
use Illuminate\Support\Facades\DB;

class WalletService
{
    protected $gateway;

    public function __construct(PaymentGatewayAdapterInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Add a new card to the device's wallet.
     *
     * @param Device $device
     * @param array $data ['pan', 'cvv', 'expiry_month', 'expiry_year', 'holder_name', 'scheme']
     * @return Card
     */
    public function addCard(Device $device, array $data): Card
    {
        // 1. Tokenize with the external provider
        // We pass the sensitive data to the adapter, which sends it to the bank/gateway.
        // The adapter returns a safe token reference.
        $tokenReference = $this->gateway->tokenize([
            'pan' => $data['pan'],
            'cvv' => $data['cvv'],
            'expiry_month' => $data['expiry_month'],
            'expiry_year' => $data['expiry_year'],
            'holder_name' => $data['holder_name'] ?? '',
        ]);

        // 2. Store the card metadata (NO PAN, NO CVV)
        // We only store the last 4 digits for display purposes.
        $maskedPan = substr($data['pan'], -4);
        
        // Generate a fingerprint to prevent duplicates (simple hash of PAN + Expiry)
        // In a real scenario, the gateway might provide a fingerprint.
        $fingerprint = hash('sha256', $data['pan'] . $data['expiry_month'] . $data['expiry_year']);

        // Check for duplicates for this device
        $existingCard = $device->cards()->where('fingerprint', $fingerprint)->first();
        if ($existingCard) {
            throw new \Exception(__('messages.card_exists'), 409);
        }

        return DB::transaction(function () use ($device, $tokenReference, $maskedPan, $data, $fingerprint) {
            // If this is the first card, make it default
            $isDefault = $device->cards()->doesntExist();

            return Card::create([
                'device_id' => $device->id,
                'token_reference' => $tokenReference,
                'masked_pan' => $maskedPan,
                'scheme' => $data['scheme'], // e.g., 'visa', 'mastercard'
                'card_art' => null, // Can be assigned based on scheme later
                'is_default' => $isDefault,
                'fingerprint' => $fingerprint,
            ]);
        });
    }

    /**
     * List cards for a device.
     */
    public function getCards(Device $device)
    {
        return $device->cards()->orderBy('created_at', 'desc')->get();
    }

    /**
     * Remove a card.
     */
    public function removeCard(Device $device, $cardId)
    {
        $card = $device->cards()->findOrFail($cardId);
        
        // Remove from gateway
        $this->gateway->deleteToken($card->token_reference);

        $card->delete();
    }

    /**
     * Set a card as default.
     */
    public function setDefaultCard(Device $device, $cardId)
    {
        return DB::transaction(function () use ($device, $cardId) {
            // Verify card belongs to device
            $card = $device->cards()->findOrFail($cardId);

            // Unset all other cards
            $device->cards()->where('id', '!=', $cardId)->update(['is_default' => false]);

            // Set this card as default
            $card->update(['is_default' => true]);

            return $card;
        });
    }
}
