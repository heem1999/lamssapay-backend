<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function index(Request $request)
    {
        // The device is identified by the middleware and attached to the request
        $device = $request->attributes->get('device');

        $cards = $this->walletService->getCards($device);

        return response()->json([
            'data' => $cards
        ]);
    }

    public function store(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('WalletController::store hit', ['request' => $request->except(['pan', 'cvv'])]);

        $device = $request->attributes->get('device');

        $validator = Validator::make($request->all(), [
            'pan' => 'required|string|min:13|max:19',
            'cvv' => 'required|string|min:3|max:4',
            'expiry_month' => 'required|string|size:2',
            'expiry_year' => 'required|string|size:2',
            'holder_name' => 'nullable|string',
            'scheme' => 'required|string|in:visa,mastercard,mada,amex',
        ]);

        if ($validator->fails()) {
            \Illuminate\Support\Facades\Log::warning('WalletController::store validation failed', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $card = $this->walletService->addCard($device, $request->all());
            return response()->json([
                'message' => 'Card added successfully',
                'data' => $card
            ], 201);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Add Card Failed: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error($e->getTraceAsString());
            return response()->json(['message' => 'Failed to add card: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $device = $request->attributes->get('device');

        try {
            $this->walletService->removeCard($device, $id);
            return response()->json(['message' => 'Card removed successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to remove card'], 500);
        }
    }
}
