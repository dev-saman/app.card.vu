<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RegisterController;

/*
|--------------------------------------------------------------------------
| API Routes - Card.vu Backend
|--------------------------------------------------------------------------
*/

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app'    => config('app.name'),
        'env'    => config('app.env'),
    ]);
});

// -------------------------------------------------------------------------
// Auth routes (public)
// -------------------------------------------------------------------------
Route::prefix('auth')->group(function () {
    // Legacy Registration
    Route::post('/register/professional', [AuthController::class, 'registerProfessional']);
    Route::post('/register/brand',        [AuthController::class, 'registerBrand']);

    // New 3-step Registration
    Route::post('/register/init',       [RegisterController::class, 'init']);
    Route::post('/register/account',    [RegisterController::class, 'account']);
    Route::post('/register/verify',     [RegisterController::class, 'verify']);
    Route::post('/register/resend-otp', [RegisterController::class, 'resendOtp']);

    // Login via email + password
    Route::post('/login', [AuthController::class, 'login']);

    // Login via mobile number + OTP (WhatsApp)
    Route::post('/login/send-otp',   [AuthController::class, 'sendLoginOtp']);
    Route::post('/login/verify-otp', [AuthController::class, 'verifyLoginOtp']);

    // Brand URL availability check
    Route::get('/check-url', [AuthController::class, 'checkBrandUrl']);

    // Protected auth routes
    Route::middleware('auth:api')->group(function () {
        Route::post('/logout',  [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me',       [AuthController::class, 'me']);
    });
});
    
// -------------------------------------------------------------------------
// Protected routes (JWT required)
// -------------------------------------------------------------------------
Route::middleware('auth:api')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
