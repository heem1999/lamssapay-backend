<?php

namespace App\Services\Merchant;

use App\Models\Merchant;
use App\Models\MerchantRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MerchantService
{
    /**
     * Submit a new merchant application.
     */
    public function submitRequest(User $user, array $data): MerchantRequest
    {
        return MerchantRequest::create([
            'user_id' => $user->id,
            'settlement_card_token' => $data['settlement_card_token'],
            'device_id' => $data['device_id'],
            'business_name' => $data['business_name'] ?? null,
            'business_type' => $data['business_type'] ?? null,
            'business_registration_number' => $data['business_registration_number'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
            'business_email' => $data['business_email'] ?? null,
            'business_phone' => $data['business_phone'] ?? null,
            'business_address' => $data['business_address'] ?? null,
            'documents' => $data['documents'] ?? [],
            'status' => 'pending',
        ]);
    }

    /**
     * Approve a merchant request and create the merchant profile.
     */
    public function approveRequest(MerchantRequest $request, User $reviewer): Merchant
    {
        return DB::transaction(function () use ($request, $reviewer) {
            $request->update([
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            // Create Merchant Profile
            $merchant = Merchant::create([
                'user_id' => $request->user_id,
                'business_name' => $request->business_name,
                'business_email' => $request->business_email,
                'business_phone' => $request->business_phone,
                'business_address' => $request->business_address,
                'tax_id' => $request->tax_id,
                'registration_number' => $request->business_registration_number,
                'status' => 'active',
                'api_key_live' => 'sk_live_' . Str::random(32),
                'api_key_test' => 'sk_test_' . Str::random(32),
            ]);

            // Update User Role
            $request->user->update(['role' => 'merchant']);

            return $merchant;
        });
    }

    /**
     * Reject a merchant request.
     */
    public function rejectRequest(MerchantRequest $request, User $reviewer, string $reason): void
    {
        $request->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Regenerate API Keys for a merchant.
     */
    public function rotateApiKeys(Merchant $merchant, string $environment = 'test'): Merchant
    {
        $key = 'sk_' . $environment . '_' . Str::random(32);
        
        if ($environment === 'live') {
            $merchant->api_key_live = $key;
        } else {
            $merchant->api_key_test = $key;
        }

        $merchant->save();

        return $merchant;
    }
}
