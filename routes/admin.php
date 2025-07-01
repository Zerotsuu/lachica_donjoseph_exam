<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\UserApiController;
use Illuminate\Support\Facades\Route;

// Web Admin Routes (for browser/Inertia views)
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    // Dashboard Routes
    Route::get('/dashboard/products', [AdminDashboardController::class, 'products'])->name('admin.dashboard.products');
    Route::get('/dashboard/orders', [AdminDashboardController::class, 'orders'])->name('admin.dashboard.orders');
    Route::get('/dashboard/users', [AdminDashboardController::class, 'users'])->name('admin.dashboard.users');
    
    // Products Management (Web)
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
    
    // Orders Management (Web)
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
    
    // Users Management (Web)
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

// API Admin Routes (for programmatic/AJAX access)
Route::middleware(['auth:sanctum'])->prefix('api/admin')->group(function () {
    
    // Orders API Routes
    Route::apiResource('orders', OrderApiController::class)->names([
        'index' => 'api.admin.orders.index',
        'store' => 'api.admin.orders.store', 
        'show' => 'api.admin.orders.show',
        'update' => 'api.admin.orders.update',
        'destroy' => 'api.admin.orders.destroy',
    ]);
    Route::patch('orders/{order}/cancel', [OrderApiController::class, 'cancel'])->name('api.admin.orders.cancel');
    
    // Users API Routes
    Route::apiResource('users', UserApiController::class)->names([
        'index' => 'api.admin.users.index',
        'store' => 'api.admin.users.store',
        'show' => 'api.admin.users.show', 
        'update' => 'api.admin.users.update',
        'destroy' => 'api.admin.users.destroy',
    ]);
    Route::patch('users/{user}/toggle-verification', [UserApiController::class, 'toggleVerification'])->name('api.admin.users.toggle-verification');
    Route::patch('users/{user}/reset-password', [UserApiController::class, 'resetPassword'])->name('api.admin.users.reset-password');
    
    // Dashboard data endpoints
    Route::get('dashboard/orders', [OrderApiController::class, 'index'])->name('api.admin.dashboard.orders');
    Route::get('dashboard/users', [UserApiController::class, 'index'])->name('api.admin.dashboard.users');
});

// Main dashboard route (redirect to products)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'products'])->name('dashboard');
}); 