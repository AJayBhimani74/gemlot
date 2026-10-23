<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\InquiryController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\StoneController;
use App\Models\Order;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('logout');

        Route::middleware('role:buyer,admin')->group(function () {
            Route::get('/stones', [StoneController::class, 'index'])->name('stones.index');
            Route::get('/stones/{stone}', [StoneController::class, 'show'])->name('stones.show');
        });

        Route::post('/stones/{stone}/inquiries', [InquiryController::class, 'store'])
            ->middleware('role:buyer')
            ->name('inquiries.store');
        Route::post('/inquiries/{inquiry}/withdraw', [InquiryController::class, 'withdraw'])
            ->middleware('role:buyer')
            ->name('inquiries.withdraw');

        Route::middleware(['role:seller', 'approved'])->group(function () {
            Route::post('/inquiries/{inquiry}/accept', [InquiryController::class, 'accept'])->name('inquiries.accept');
            Route::post('/inquiries/{inquiry}/decline', [InquiryController::class, 'decline'])->name('inquiries.decline');
        });

        Route::get('/inquiries', [InquiryController::class, 'index'])
            ->middleware('role:buyer,seller')
            ->name('inquiries.index');

        Route::middleware('role:buyer,seller,admin')->group(function () {
            Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
            Route::post('/orders/{order}/{action}', [OrderController::class, 'transition'])
                ->whereIn('action', array_keys(Order::ACTIONS))
                ->name('orders.transition');
        });
    });
});
