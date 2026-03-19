<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

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
    // Registration
    Route::post('/register/professional', [AuthController::class, 'registerProfessional']);
    Route::post('/register/brand',        [AuthController::class, 'registerBrand']);

    // Login
    Route::post('/login', [AuthController::class, 'login']);

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
