<?php

use App\Http\Controllers\Account\AccountOrderController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\SavedAddressController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PartController;
use App\Http\Controllers\PayseraWebhookController;
use App\Http\Controllers\VinLookupController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/parts', [PartController::class, 'index'])->name('parts.index');
Route::get('/parts/{part:slug}', [PartController::class, 'show'])->name('parts.show');
Route::get('/categories/{category:slug}', [PartController::class, 'index'])->name('categories.show');

Route::get('/vin-lookup', VinLookupController::class)->name('vin-lookup.show');

Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/items/{part}', [CartController::class, 'store'])->name('items.store');
    Route::patch('/items/{part}', [CartController::class, 'update'])->name('items.update');
    Route::delete('/items/{partId}', [CartController::class, 'destroy'])->name('items.destroy');
});

Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/checkout', [CheckoutController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('checkout.store');

// Public guest-or-owner receipt link — access is the unguessable UUID
// itself (see OrderPolicy), not login.
Route::get('/orders/{order:uuid}', [OrderController::class, 'show'])->name('orders.show');
Route::post('/orders/{order:uuid}/pay', [OrderController::class, 'pay'])->name('orders.pay');

// Paysera calls this directly — no CSRF token, see the exclusion in
// bootstrap/app.php. Signature verification (PayseraSignature) is what
// actually authenticates the request instead.
Route::post('/paysera/webhook', PayseraWebhookController::class)->name('paysera.webhook');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register.create');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
    Route::get('/login', [LoginController::class, 'create'])->name('login.create');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/', [ProfileController::class, 'show'])->name('profile');

    Route::get('/orders', [AccountOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order:uuid}', [AccountOrderController::class, 'show'])->name('orders.show');

    Route::prefix('addresses')->name('addresses.')->group(function () {
        Route::get('/', [SavedAddressController::class, 'index'])->name('index');
        Route::get('/create', [SavedAddressController::class, 'create'])->name('create');
        Route::post('/', [SavedAddressController::class, 'store'])->name('store');
        Route::get('/{address}/edit', [SavedAddressController::class, 'edit'])->name('edit');
        Route::put('/{address}', [SavedAddressController::class, 'update'])->name('update');
        Route::delete('/{address}', [SavedAddressController::class, 'destroy'])->name('destroy');
    });
});
