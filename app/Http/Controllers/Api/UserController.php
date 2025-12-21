<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Update user profile.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['string', 'max:255'],
            'phone_number' => ['string', 'max:20'],
            'email' => ['email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'preferences' => ['array'],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'data' => $user,
        ]);
    }

    /**
     * Submit KYC documents.
     */
    public function submitKyc(Request $request): JsonResponse
    {
        $request->validate([
            'document_type' => 'required|in:passport,national_id,driving_license',
            'document_number' => 'required|string',
            // In real app, handle file uploads
            'front_image_path' => 'required|string', 
        ]);

        $kyc = $request->user()->kycRecords()->create([
            'document_type' => $request->document_type,
            'document_number' => $request->document_number,
            'front_image_path' => $request->front_image_path,
            'status' => 'pending',
        ]);

        $request->user()->update(['kyc_status' => 'pending']);

        return response()->json([
            'message' => 'KYC documents submitted successfully.',
            'data' => $kyc,
        ]);
    }
}
