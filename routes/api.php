<?php

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrderStatusController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\QuoteController;
use App\Http\Controllers\Api\V1\QuoteConversionController;
use App\Http\Controllers\Api\V1\QuoteStatusController;
use Illuminate\Support\Facades\Route;

// Mounted under /api by the framework; everything here lives under /api/v1.
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/token', [AuthTokenController::class, 'store'])->middleware('throttle:login')->name('token.store');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::delete('auth/token', [AuthTokenController::class, 'destroy'])->name('token.destroy');

        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('products', ProductController::class);

        Route::apiResource('quotes', QuoteController::class);
        Route::post('quotes/{quote}/status', QuoteStatusController::class)->name('quotes.status');
        Route::post('quotes/{quote}/convert', QuoteConversionController::class)->name('quotes.convert');

        Route::apiResource('orders', OrderController::class)->only(['index', 'show']);
        Route::post('orders/{order}/status', OrderStatusController::class)->name('orders.status');
    });
});
