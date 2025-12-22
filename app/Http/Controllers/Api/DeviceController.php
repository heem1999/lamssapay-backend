<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Device\DeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeviceController extends Controller
{
    protected $deviceService;

    public function __construct(DeviceService $deviceService)
    {
        $this->deviceService = $deviceService;
    }

    /**
     * Perform a device handshake to establish anonymous identity.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function handshake(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:100',
            'device_name' => 'nullable|string|max:100',
            'platform' => 'required|string|in:android,ios,web',
            'os_version' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $device = $this->deviceService->registerHandshake(
            $validator->validated(),
            $request->ip()
        );

        // Issue a device-scoped token (Sanctum)
        // Note: We are issuing a token to a Device model, not a User model yet.
        // This requires the Device model to use the HasApiTokens trait.
        // For Phase 1, we might just return success, or if we want strict security,
        // we can implement token issuance here. For now, we confirm the handshake.

        return response()->json([
            'message' => 'Device handshake successful',
            'status' => 'active',
            'device_id' => $device->device_id
        ]);
    }
}
