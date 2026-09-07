<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PartController;
use App\Http\Controllers\VinLookupController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/parts', [PartController::class, 'index'])->name('parts.index');
Route::get('/parts/{part:slug}', [PartController::class, 'show'])->name('parts.show');
Route::get('/categories/{category:slug}', [PartController::class, 'index'])->name('categories.show');

Route::get('/vin-lookup', VinLookupController::class)->name('vin-lookup.show');
