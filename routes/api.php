<?php

use App\Http\Controllers\Api\V1\ExchangeRateController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware('throttle:api')
    ->group(function () {
        Route::get('/exchange-rates', [ExchangeRateController::class, 'index']);
    });
