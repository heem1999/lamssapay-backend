<?php

use App\Http\Controllers\Api\AdminMerchantController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\MerchantController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

Log::info('Routes V1 loaded');

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
*/

// Device Handshake (Anonymous Entry Point)
Route::post('/device/handshake', [DeviceController::class, 'handshake']);

// Payment Network Simulation (Phase 7)
Route::post('/transactions/authorize', [TransactionController::class, 'authorizePayment']);

// Public Routes (Now protected by Device Gateway)
Route::middleware(['device.gateway'])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login/verify-2fa', [AuthController::class, 'verifyTwoFactor']);

    // Wallet Routes
    Route::prefix('wallet')->group(function () {
        Route::get('/cards', [WalletController::class, 'index']);
        Route::post('/cards', [WalletController::class, 'store']);
        Route::post('/cards/{id}/verify', [WalletController::class, 'verify']);
        Route::delete('/cards/{id}', [WalletController::class, 'destroy']);
        Route::post('/cards/{id}/default', [WalletController::class, 'setDefault']);
    });
});

// Protected Routes (User Auth + Device Gateway)
Route::middleware(['auth:sanctum', 'device.gateway'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Two-Factor Authentication Management
    Route::prefix('2fa')->group(function () {
        Route::post('/enable', [AuthController::class, 'enableTwoFactor']);
        Route::post('/confirm', [AuthController::class, 'confirmTwoFactor']);
        Route::post('/disable', [AuthController::class, 'disableTwoFactor']);
    });

    // User Profile
    Route::put('/user/profile', [UserController::class, 'update']);
    Route::post('/user/kyc', [UserController::class, 'submitKyc']);

    // Merchant Management
    Route::post('/merchants/register', [MerchantController::class, 'register']);
    Route::get('/merchants/me', [MerchantController::class, 'me']);
    Route::post('/merchants/rotate-keys', [MerchantController::class, 'rotateKeys']);

    // Transaction Management
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show']);
    Route::post('/transactions/transfer', [TransactionController::class, 'transfer']);
});

// Phase 10: Merchant Mode (Device Bound)
Route::middleware(['device.gateway'])->group(function () {
    Route::post('/merchant/request', [MerchantController::class, 'requestAccess']);
    Route::get('/merchant/request/status', [MerchantController::class, 'checkStatus']);
    Route::post('/merchant/request/{id}/cancel', [MerchantController::class, 'cancel']);
});

// Admin Routes (Unprotected for MVP Demo)
Route::prefix('admin')->group(function () {
    Route::get('/merchants/requests', [AdminMerchantController::class, 'index']);
    Route::post('/merchants/requests/{id}/approve', [AdminMerchantController::class, 'approve']);
    Route::post('/merchants/requests/{id}/reject', [AdminMerchantController::class, 'reject']);
});

// Device-only Protected Routes (No User Auth required, just Device Auth)
Route::middleware(['device.gateway'])->group(function () {
    Route::post('wallets/validate', [WalletController::class, 'validateCard']);

    // Wallet & Card Management (Device Bound)
    Route::prefix('wallet/cards')->group(function () {
        Route::get('/', [WalletController::class, 'index']);
        Route::post('/', [WalletController::class, 'store']);
        Route::post('/{id}/verify', [WalletController::class, 'verify']);
        Route::delete('/{id}', [WalletController::class, 'destroy']);
        Route::post('/{id}/default', [WalletController::class, 'setDefault']);
    });
});
