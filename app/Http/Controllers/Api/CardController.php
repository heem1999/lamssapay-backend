<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Wallet\CardVaultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CardController extends Controller
{
    protected $cardVaultService;

    public function __construct(CardVaultService $cardVaultService)
    {
        $this->cardVaultService = $cardVaultService;
    }

    /**
     * List user cards.
     */
    public function index(Request $request): JsonResponse
    {
        \Illuminate\Support\Facades\Log::info('CardController index hit');
        $user = $request->user();
        $cards = $user->cards;
        return response()->json(['data' => $cards]);
    }

    /**
     * Add a new card.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'pan' => 'required|string|min:13|max:19',
            'cvv' => 'required|string|min:3|max:4',
            'expiry_month' => 'required|string|size:2',
            'expiry_year' => 'required|string|size:4',
            'card_holder_name' => 'required|string',
            'billing_address' => 'nullable|array',
            'is_default' => 'boolean',
        ]);

        $user = $request->user() ?? \App\Models\User::first();
        $card = $this->cardVaultService->storeCard($user, $request->all());

        return response()->json([
            'message' => 'Card added successfully.',
            'data' => $card,
        ], 201);
    }

    /**
     * Remove a card.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $card = $request->user()->cards()->findOrFail($id);
        
        $this->cardVaultService->deleteCard($card);

        return response()->json([
            'message' => 'Card removed successfully.',
        ]);
    }
}
