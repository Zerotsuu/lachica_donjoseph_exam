<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Middleware\SanctumAdminMiddleware;
use Illuminate\Support\Facades\Route;

// Authentication routes (no middleware)
Route::post('auth/login', [AuthController::class, 'login'])->name('api.auth.login');

// Sanctum-protected auth routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    Route::get('auth/me', [AuthController::class, 'me'])->name('api.auth.me');
    Route::post('auth/revoke-all', [AuthController::class, 'revokeAll'])->name('api.auth.revoke-all');
});

// Sanctum-protected admin API routes
Route::middleware(['auth:sanctum', 'sanctum.admin'])->prefix('admin')->group(function () {
    
    // Orders API Routes
    Route::apiResource('orders', OrderApiController::class);
    Route::patch('orders/{order}/cancel', [OrderApiController::class, 'cancel'])->name('api.admin.orders.cancel');
    
    // Users API Routes
    Route::apiResource('users', UserApiController::class);
    Route::patch('users/{user}/toggle-verification', [UserApiController::class, 'toggleVerification'])->name('api.admin.users.toggle-verification');
    Route::patch('users/{user}/reset-password', [UserApiController::class, 'resetPassword'])->name('api.admin.users.reset-password');
    
    // Dashboard data endpoints
    Route::get('dashboard/orders', [OrderApiController::class, 'index'])->name('api.admin.dashboard.orders');
    Route::get('dashboard/users', [UserApiController::class, 'index'])->name('api.admin.dashboard.users');
}); 