<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExchangeRateController;
use App\Http\Controllers\Admin\GoldRateController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'create'])->name('login');
        Route::post('/login', [AuthController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/exchange-rates', [ExchangeRateController::class, 'index'])->name('exchange-rates.index');
        Route::put('/exchange-rates/{exchangeRate}', [ExchangeRateController::class, 'update'])->name('exchange-rates.update');
        Route::get('/gold-rates', [GoldRateController::class, 'index'])->name('gold-rates.index');
        Route::put('/gold-rates/{goldRate}', [GoldRateController::class, 'update'])->name('gold-rates.update');
    });
});
