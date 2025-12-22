<?php

namespace App\Services;

use App\Interfaces\PaymentGatewayAdapterInterface;
use App\Interfaces\IssuerAdapterInterface;
use App\Interfaces\NotificationAdapterInterface;
use App\Models\Card;
use App\Models\Device;
use Illuminate\Support\Facades\DB;

class WalletService
{
    protected $gateway;
    protected $issuer;
    protected $notification;

    public function __construct(
        PaymentGatewayAdapterInterface $gateway, 
        IssuerAdapterInterface $issuer,
        NotificationAdapterInterface $notification
    ) {
        $this->gateway = $gateway;
        $this->issuer = $issuer;
        $this->notification = $notification;
    }

    /**
     * Validate card eligibility and check for duplicates.
     *
     * @param Device $device
     * @param array $data
     * @return bool
     */
    public function validateCard(Device $device, array $data): bool
    {
        // 1. Check for duplicates (Fingerprint)
        $fingerprint = hash('sha256', $data['pan'] . $data['expiry_month'] . $data['expiry_year']);
        $existingCard = $device->cards()->where('fingerprint', $fingerprint)->first();
        
        if ($existingCard) {
            throw new \Exception(__('messages.card_exists'), 409);
        }

        // 2. Check with Issuer (Mock check - usually initiateVerification does this, but we can have a pre-check)
        // For now, we assume if initiateVerification works, it's valid.
        // But to separate concerns, we could add a 'checkEligibility' method to IssuerAdapter.
        // For this task, we'll assume basic validation is enough.
        
        return true;
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
        // 0. Re-validate (Fail fast)
        $this->validateCard($device, $data);

        // 1. Tokenize with the external provider
        $tokenReference = $this->gateway->tokenize([
            'pan' => $data['pan'],
            'cvv' => $data['cvv'],
            'expiry_month' => $data['expiry_month'],
            'expiry_year' => $data['expiry_year'],
            'holder_name' => $data['holder_name'] ?? '',
        ]);

        // 2. Initiate Issuer Verification (OTP)
        $verificationData = $this->issuer->initiateVerification($data);
        $issuerReference = $verificationData['reference'];
        $maskedPhone = $verificationData['masked_phone'];

        // 3. Store the card metadata (NO PAN, NO CVV)
        $maskedPan = substr($data['pan'], -4);
        $fingerprint = hash('sha256', $data['pan'] . $data['expiry_month'] . $data['expiry_year']);

        return DB::transaction(function () use ($device, $tokenReference, $maskedPan, $data, $fingerprint, $issuerReference, $maskedPhone) {
            // If this is the first card, make it default
            $isDefault = $device->cards()->doesntExist();

            $card = Card::create([
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

            $card->verification_masked_phone = $maskedPhone;
            
            // 4. Send OTP via Notification Service
            // In a real scenario, we might use the reference or the phone if we had it.
            // Here we simulate sending to the masked phone (logging it).
            $this->notification->sendOtp($maskedPhone, "1234");

            return $card;
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
        // Show active and pending cards
        return $device->cards()
            ->whereIn('status', ['active', 'pending'])
            ->orderBy('created_at', 'desc')
            ->get();
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
