<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\SanctumAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', SanctumAdminMiddleware::class])->prefix('admin')->group(function () {
    // Dashboard Routes
    Route::get('/dashboard/products', [AdminDashboardController::class, 'products'])->name('admin.dashboard.products');
    Route::get('/dashboard/orders', [AdminDashboardController::class, 'orders'])->name('admin.dashboard.orders');
    Route::get('/dashboard/users', [AdminDashboardController::class, 'users'])->name('admin.dashboard.users');
    
    // Products Management
    Route::resource('products', ProductController::class)->names([
        'index' => 'admin.products.index',
        'create' => 'admin.products.create',
        'store' => 'admin.products.store',
        'show' => 'admin.products.show',
        'edit' => 'admin.products.edit',
        'update' => 'admin.products.update',
        'destroy' => 'admin.products.destroy',
    ]);
    Route::get('/products/api', [ProductController::class, 'api'])->name('admin.products.api');
    
    // Orders Management  
    Route::resource('orders', OrderController::class)->names([
        'index' => 'admin.orders.index',
        'create' => 'admin.orders.create',
        'store' => 'admin.orders.store',
        'show' => 'admin.orders.show',
        'edit' => 'admin.orders.edit',
        'update' => 'admin.orders.update',
        'destroy' => 'admin.orders.destroy',
    ]);
    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('admin.orders.cancel');
    
    // Users Management
    Route::resource('users', UserController::class)->names([
        'index' => 'admin.users.index',
        'create' => 'admin.users.create',
        'store' => 'admin.users.store',
        'show' => 'admin.users.show',
        'edit' => 'admin.users.edit',
        'update' => 'admin.users.update',
        'destroy' => 'admin.users.destroy',
    ]);
    Route::patch('/users/{user}/toggle-verification', [UserController::class, 'toggleVerification'])->name('admin.users.toggle-verification');
    Route::patch('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('admin.users.reset-password');
});

// Main dashboard route (redirect to products)
Route::middleware(['auth:sanctum', SanctumAdminMiddleware::class])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'products'])->name('dashboard');
}); 