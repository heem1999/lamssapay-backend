<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\MerchantController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

Log::info('Routes V1 loaded');

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
*/

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/verify-2fa', [AuthController::class, 'verifyTwoFactor']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
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
