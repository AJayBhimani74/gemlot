<?php

use App\Http\Controllers\Admin\SellerController as AdminSellerController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CatalogueController;
use App\Http\Controllers\DashboardController;
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
