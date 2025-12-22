<?php

namespace App\Http\Middleware;

use App\Services\Device\DeviceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeviceGatewayMiddleware
{
    protected $deviceService;

    public function __construct(DeviceService $deviceService)
    {
        $this->deviceService = $deviceService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Extract Device Fingerprint
        $deviceId = $request->header('X-Device-Fingerprint');

        // Allow handshake endpoint to bypass this check
        if ($request->is('api/v1/device/handshake')) {
            return $next($request);
        }

        if (!$deviceId) {
            return response()->json([
                'error' => 'Device Identity Missing',
                'message' => 'X-Device-Fingerprint header is required.'
            ], 400);
        }

        // 2. Validate Device Status via Service
        if (!$this->deviceService->isDeviceAllowed($deviceId)) {
            return response()->json([
                'error' => 'Device Access Denied',
                'message' => 'This device is not registered or has been blocked.'
            ], 403);
        }

        // 3. (Optional) Rate Limiting Logic could go here or via standard throttle middleware

        return $next($request);
    }
}
