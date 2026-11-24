<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PosController;
use Illuminate\Support\Facades\Route;

// SUNMI app API. Every route except login needs a token from /api/v1/login.
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1')->name('login');

    Route::middleware(['auth:sanctum', 'abilities:pos', 'active', 'throttle:120,1'])->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/menu', [PosController::class, 'menu'])->name('menu');
        Route::post('/orders/quote', [PosController::class, 'quote'])->name('orders.quote');
        Route::post('/orders', [PosController::class, 'store'])->name('orders.store');
        Route::get('/orders/today', [PosController::class, 'today'])->name('orders.today');
        Route::get('/orders/{order}/receipt', [PosController::class, 'receipt'])->name('orders.receipt');
    });
});
