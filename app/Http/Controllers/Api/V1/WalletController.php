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
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $card = $this->walletService->addCard($device, $request->all());
            return response()->json([
                'message' => __('messages.card_added'),
                'data' => $card
            ], 201);
        } catch (\Exception $e) {
            $status = $e->getCode() === 409 ? 409 : 500;
            return response()->json(['message' => $e->getMessage()], $status);
        }
    }

    public function destroy(Request $request, $id)
    {
        $device = $request->attributes->get('device');

        try {
            $this->walletService->removeCard($device, $id);
            return response()->json(['message' => __('messages.card_removed')]);
        } catch (\Exception $e) {
            return response()->json(['message' => __('messages.card_remove_failed')], 500);
        }
    }

    public function setDefault(Request $request, $id)
    {
        $device = $request->attributes->get('device');

        try {
            $card = $this->walletService->setDefaultCard($device, $id);
            return response()->json([
                'message' => __('messages.card_default_set'),
                'data' => $card
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => __('messages.card_default_failed')], 500);
        }
    }
}
