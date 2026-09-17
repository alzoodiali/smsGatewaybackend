<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\GatewayController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // --- Client API ---
    Route::prefix('client')->group(function () {
        // Silent pairing (unprotected)
        Route::post('/connect', [ClientController::class, 'connect']);

        // Authenticated client routes
        Route::middleware(['client.api_key'])->group(function () {
            Route::post('/otp/request', [ClientController::class, 'sendOtp']);
            Route::post('/otp/verify', [ClientController::class, 'verifyOtp']);
        });
    });

    // --- Gateway API ---
    Route::prefix('gateway')->group(function () {
        // Registration (unprotected)
        Route::post('/register', [GatewayController::class, 'register']);

        // Authenticated gateway routes
        Route::middleware(['gateway.token'])->group(function () {
            Route::post('/authenticate', [GatewayController::class, 'authenticate']);
            Route::post('/heartbeat', [GatewayController::class, 'heartbeat']);
            Route::post('/jobs/next', [GatewayController::class, 'poll']);
            Route::post('/jobs/{id}/result', [GatewayController::class, 'updateJobResult']);
        });
    });

});
