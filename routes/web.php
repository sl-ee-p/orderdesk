<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderStatusController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\QuoteConversionController;
use App\Http\Controllers\QuoteStatusController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');
});

Route::middleware('auth')->group(function () {
    Route::redirect('/', '/quotes');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::resource('customers', CustomerController::class);
    Route::resource('products', ProductController::class)->except('show');

    Route::resource('quotes', QuoteController::class);
    Route::post('quotes/{quote}/status', QuoteStatusController::class)->name('quotes.status');
    Route::post('quotes/{quote}/convert', QuoteConversionController::class)->name('quotes.convert');

    Route::resource('orders', OrderController::class)->only(['index', 'show']);
    Route::post('orders/{order}/status', OrderStatusController::class)->name('orders.status');
});
