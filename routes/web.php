<?php

use App\Http\Controllers\Admin\SellerController as AdminSellerController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CatalogueController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Seller\StoneController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});

Route::middleware(['auth', 'role:seller', 'approved'])
    ->prefix('seller')
    ->name('seller.')
    ->group(function () {
        Route::resource('stones', StoneController::class)->except('show');
    });

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/sellers', [AdminSellerController::class, 'index'])->name('sellers.index');
        Route::post('/sellers/{seller}/approve', [AdminSellerController::class, 'approve'])->name('sellers.approve');
        Route::post('/sellers/{seller}/revoke', [AdminSellerController::class, 'revoke'])->name('sellers.revoke');
    });

Route::middleware(['auth', 'role:buyer,admin'])->group(function () {
    Route::get('/stones', [CatalogueController::class, 'index'])->name('catalogue.index');
    Route::get('/stones/{stone}', [CatalogueController::class, 'show'])->name('catalogue.show');
});

Route::middleware(['auth', 'role:buyer'])->group(function () {
    Route::post('/stones/{stone}/inquiries', [InquiryController::class, 'store'])->name('inquiries.store');
    Route::post('/inquiries/{inquiry}/withdraw', [InquiryController::class, 'withdraw'])->name('inquiries.withdraw');
});

Route::middleware(['auth', 'role:seller', 'approved'])->group(function () {
    Route::post('/inquiries/{inquiry}/accept', [InquiryController::class, 'accept'])->name('inquiries.accept');
    Route::post('/inquiries/{inquiry}/decline', [InquiryController::class, 'decline'])->name('inquiries.decline');
});

Route::middleware(['auth', 'role:buyer,seller'])->get('/inquiries', [InquiryController::class, 'index'])->name('inquiries.index');

Route::middleware(['auth', 'role:buyer,seller,admin'])->group(function () {
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/{action}', [OrderController::class, 'transition'])
        ->whereIn('action', array_keys(\App\Models\Order::ACTIONS))
        ->name('orders.transition');
});
