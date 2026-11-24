<?php

use App\Http\Controllers\Admin\AddonController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PosController;
use Illuminate\Support\Facades\Route;

// NOTE: login + role permissions arrive in Phase 2. Until then, run this on a
// trusted local machine only.

Route::redirect('/', '/pos');
Route::get('/pos', [PosController::class, 'index'])->name('pos');

Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
Route::get('/orders/{order}/receipt', [OrderController::class, 'receipt'])->name('orders.receipt');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('addons', AddonController::class)->only(['index', 'store', 'update']);
    Route::resource('products', ProductController::class)->except(['show', 'destroy']);
    Route::patch('products/{product}/availability', [ProductController::class, 'toggleAvailability'])->name('products.availability');
    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
});
