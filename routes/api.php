<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ExchangeRateController;
use App\Http\Controllers\Api\V1\GoldRateController;
use App\Http\Controllers\Api\V1\MarketSummaryController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware('throttle:api')
    ->group(function () {
        Route::get('/exchange-rates', [ExchangeRateController::class, 'index']);
        Route::get('/gold-rates', [GoldRateController::class, 'index']);
        Route::get('/market-summary', [MarketSummaryController::class, 'show']);

        Route::post('/auth/login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/auth/logout', [AuthController::class, 'logout']);
            Route::get('/auth/me', [AuthController::class, 'me']);
        });
    });
