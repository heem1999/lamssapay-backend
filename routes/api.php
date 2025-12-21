<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Load V1 Routes with /v1 prefix (e.g., /api/v1/login)
Route::prefix('v1')->group(base_path('routes/v1.php'));

// Load V1 Routes without prefix for backward compatibility (e.g., /api/login)
require base_path('routes/v1.php');

Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');

