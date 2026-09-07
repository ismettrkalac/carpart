<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PartController;
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

Route::get('/orders/{order:uuid}', [OrderController::class, 'show'])->name('orders.show');
