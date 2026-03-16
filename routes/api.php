<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Authenticated user route (JWT protected)
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');
