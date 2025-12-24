<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use App\Models\MerchantRequest;
use Illuminate\Http\Request;

class TransactionHistoryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
            'card_token' => 'nullable|string', // For Consumer View
            'merchant_id' => 'nullable|integer', // For Merchant View
        ]);

        $query = LedgerEntry::query();

        // Consumer View: Show transactions for a specific card
        if ($request->has('card_token')) {
            $query->where('card_token', $request->card_token)
                  ->where('direction', 'DEBIT'); // Only show what they spent
        }
        // Merchant View: Show transactions for a specific merchant
        elseif ($request->has('merchant_id')) {
            // Verify device belongs to merchant
            $merchant = MerchantRequest::where('id', $request->merchant_id)
                ->where('device_id', $request->device_id)
                ->first();

            if (!$merchant) {
                return response()->json(['message' => 'Unauthorized access to merchant history'], 403);
            }

            $query->where('merchant_id', $request->merchant_id)
                  ->where('direction', 'CREDIT'); // Only show what they received
        } else {
            return response()->json(['message' => 'Either card_token or merchant_id is required'], 400);
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->limit(50) // MVP Limitation
            ->get()
            ->map(function ($entry) {
                return [
                    'transaction_id' => $entry->transaction_id,
                    'type' => 'PAYMENT',
                    'direction' => $entry->direction,
                    'amount' => (float) $entry->amount,
                    'currency' => $entry->currency,
                    'merchant_name' => 'Merchant #' . $entry->merchant_id, // In real app, join with Merchant table
                    'card_last4' => substr($entry->card_token, -4),
                    'status' => $entry->status,
                    'timestamp' => $entry->created_at->toIso8601String(),
                ];
            });

        return response()->json(['data' => $transactions]);
    }
}
