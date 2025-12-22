<?php

namespace Tests\Feature;

use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_add_card()
    {
        // 1. Create a device
        $deviceId = 'test-device-id-' . uniqid();
        $device = Device::create([
            'device_id' => $deviceId,
            'device_name' => 'Test Device',
            'platform' => 'android',
            'status' => 'active'
        ]);

        // 2. Make request with header
        $response = $this->withHeaders([
            'X-Device-Fingerprint' => $deviceId,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/wallet/cards', [
            'pan' => '4242424242424242',
            'cvv' => '123',
            'expiry_month' => '12',
            'expiry_year' => '30',
            'holder_name' => 'John Doe',
            'scheme' => 'visa'
        ]);

        // 3. Dump response if failed
        if ($response->status() !== 201) {
            dump($response->json());
        }

        $response->assertStatus(201);
    }
}
