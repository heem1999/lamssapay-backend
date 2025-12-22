<?php

namespace App\Services\Device;

use App\Models\Device;
use Illuminate\Support\Facades\Log;

class DeviceService
{
    /**
     * Register or update a device handshake.
     *
     * @param array $data Device fingerprint data
     * @param string|null $ip IP Address
     * @return Device
     */
    public function registerHandshake(array $data, ?string $ip = null): Device
    {
        return Device::updateOrCreate(
            ['device_id' => $data['device_id']],
            [
                'device_name' => $data['device_name'] ?? 'Unknown Device',
                'platform' => $data['platform'] ?? 'unknown',
                'os_version' => $data['os_version'] ?? null,
                'last_ip' => $ip,
                'last_active_at' => now(),
                'status' => 'active' // Default status
            ]
        );
    }

    /**
     * Validate if a device is allowed to access the system.
     *
     * @param string $deviceId
     * @return bool
     */
    public function isDeviceAllowed(string $deviceId): bool
    {
        $device = Device::where('device_id', $deviceId)->first();

        if (!$device) {
            return false; // Unknown device must handshake first
        }

        if ($device->status === 'blocked') {
            Log::warning("Blocked device attempted access: {$deviceId}");
            return false;
        }

        // Update activity timestamp
        $device->touch('last_active_at');
        
        return true;
    }
}
