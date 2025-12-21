<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Get all wallets for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user() ?? \App\Models\User::first();
        $wallets = $user->wallets;

        return response()->json([
            'data' => $wallets,
        ]);
    }

    /**
     * Get a specific wallet details.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user() ?? \App\Models\User::first();
        $wallet = $user->wallets()->findOrFail($id);

        return response()->json([
            'data' => $wallet,
        ]);
    }

    /**
     * Create a new wallet (e.g. for a different currency).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'currency' => 'required|string|size:3',
        ]);

        // Check if wallet already exists
        if ($this->walletService->getWallet($request->user(), $request->currency)) {
            return response()->json([
                'message' => 'Wallet for this currency already exists.',
            ], 409);
        }

        $wallet = $this->walletService->createWallet($request->user(), $request->currency);

        return response()->json([
            'message' => 'Wallet created successfully.',
            'data' => $wallet,
        ], 201);
    }
}
