<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\AddonController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\HeldCartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Site\SiteController;
use App\Http\Middleware\SetSiteLanguage;
use Illuminate\Support\Facades\Route;

/* ---------- Public website (roshbar.com): view only, no customer actions ---------- */
Route::middleware(SetSiteLanguage::class)->group(function () {
    Route::get('/', [SiteController::class, 'home'])->name('site.home');
    Route::get('/menu', [SiteController::class, 'menu'])->name('site.menu');
    Route::get('/menu/{slug}', [SiteController::class, 'product'])->name('site.product');
    Route::get('/about', [SiteController::class, 'about'])->name('site.about');
    Route::get('/contact', [SiteController::class, 'contact'])->name('site.contact');
});

/* ---------- Staff login ---------- */
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});
Route::redirect('/admin/login', '/login'); // old site's URL
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

/* ---------- POS: every active staff member ---------- */
Route::middleware('auth')->group(function () {
    Route::get('/pos', [PosController::class, 'index'])->name('pos');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::post('/orders/quote', [OrderController::class, 'quote'])->name('orders.quote');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/receipt', [OrderController::class, 'receipt'])->name('orders.receipt');
    Route::post('/orders/{order}/payments', [OrderController::class, 'pay'])->name('orders.pay');
    Route::post('/orders/{order}/status', [OrderController::class, 'advance'])->name('orders.advance');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('/pos/held', [HeldCartController::class, 'index'])->name('held.index');
    Route::post('/pos/held', [HeldCartController::class, 'store'])->name('held.store');
    Route::post('/pos/held/{heldCart}/resume', [HeldCartController::class, 'resume'])->name('held.resume');
    Route::delete('/pos/held/{heldCart}', [HeldCartController::class, 'destroy'])->name('held.destroy');
    Route::get('/account/password', [AccountController::class, 'edit'])->name('account.password');
    Route::put('/account/password', [AccountController::class, 'update'])->name('account.password.update');
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::middleware('role:owner,manager')->group(function () {
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
        Route::post('/expense-categories', [ExpenseController::class, 'storeCategory'])->name('expenses.categories.store');
        Route::put('/expense-categories/{category}', [ExpenseController::class, 'updateCategory'])->name('expenses.categories.update');
    });
});

/* ---------- Admin: owner + manager ---------- */
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:owner,manager'])->group(function () {
    Route::redirect('/', '/admin/products');
    Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('reports/sales.csv', [ReportController::class, 'salesCsv'])->name('reports.sales.csv');
    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('addons', AddonController::class)->only(['index', 'store', 'update']);
    Route::resource('products', ProductController::class)->except(['show', 'destroy']);
    Route::patch('products/{product}/availability', [ProductController::class, 'toggleAvailability'])->name('products.availability');
    Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('messages/{message}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('messages/{message}/unread', [MessageController::class, 'unread'])->name('messages.unread');
    Route::delete('messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

    Route::middleware('role:owner')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::get('activity', [ActivityController::class, 'index'])->name('activity.index');
    });
});

Route::fallback(fn () => app(SiteController::class)->notFound())->middleware(['web', SetSiteLanguage::class]);
