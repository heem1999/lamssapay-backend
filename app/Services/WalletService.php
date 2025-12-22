<?php

namespace App\Services;

use App\Interfaces\PaymentGatewayAdapterInterface;
use App\Interfaces\IssuerAdapterInterface;
use App\Models\Card;
use App\Models\Device;
use Illuminate\Support\Facades\DB;

class WalletService
{
    protected $gateway;
    protected $issuer;

    public function __construct(PaymentGatewayAdapterInterface $gateway, IssuerAdapterInterface $issuer)
    {
        $this->gateway = $gateway;
        $this->issuer = $issuer;
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
        $tokenReference = $this->gateway->tokenize([
            'pan' => $data['pan'],
            'cvv' => $data['cvv'],
            'expiry_month' => $data['expiry_month'],
            'expiry_year' => $data['expiry_year'],
            'holder_name' => $data['holder_name'] ?? '',
        ]);

        // 2. Initiate Issuer Verification (OTP)
        $issuerReference = $this->issuer->initiateVerification($data);

        // 3. Store the card metadata (NO PAN, NO CVV)
        $maskedPan = substr($data['pan'], -4);
        $fingerprint = hash('sha256', $data['pan'] . $data['expiry_month'] . $data['expiry_year']);

        // Check for duplicates for this device
        $existingCard = $device->cards()->where('fingerprint', $fingerprint)->first();
        if ($existingCard) {
            throw new \Exception(__('messages.card_exists'), 409);
        }

        return DB::transaction(function () use ($device, $tokenReference, $maskedPan, $data, $fingerprint, $issuerReference) {
            // If this is the first card, make it default
            $isDefault = $device->cards()->where('status', 'active')->doesntExist();

            return Card::create([
                'device_id' => $device->id,
                'token_reference' => $tokenReference,
                'masked_pan' => $maskedPan,
                'scheme' => $data['scheme'],
                'card_art' => null,
                'is_default' => $isDefault,
                'fingerprint' => $fingerprint,
                'status' => 'pending', // Wait for OTP
                'issuer_reference' => $issuerReference,
            ]);
        });
    }

    /**
     * Verify the card with OTP.
     */
    public function verifyCard(Device $device, $cardId, $otp)
    {
        $card = $device->cards()->findOrFail($cardId);

        if ($card->status === 'active') {
            return $card;
        }

        if ($this->issuer->validateOtp($card->issuer_reference, $otp)) {
            $card->update(['status' => 'active']);
            return $card;
        }

        throw new \Exception('Invalid OTP', 400);
    }

    /**
     * List cards for a device.
     */
    public function getCards(Device $device)
    {
        // Only show active cards
        return $device->cards()->where('status', 'active')->orderBy('created_at', 'desc')->get();
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
