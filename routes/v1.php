<?php

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

// Public Routes (Now protected by Device Gateway)
Route::middleware(['device.gateway'])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login/verify-2fa', [AuthController::class, 'verifyTwoFactor']);

    // Wallet Routes
    Route::prefix('wallet')->group(function () {
        Route::get('/cards', [WalletController::class, 'index']);
        Route::post('/cards', [WalletController::class, 'store']);
        Route::delete('/cards/{id}', [WalletController::class, 'destroy']);
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

    // Card Management
    Route::apiResource('cards', CardController::class)->only(['index', 'store', 'destroy']);

    // Wallet Management
    Route::apiResource('wallets', WalletController::class)->only(['index', 'show', 'store']);

    // Transaction Management
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show']);
    Route::post('/transactions/transfer', [TransactionController::class, 'transfer']);
});
