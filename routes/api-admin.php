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
    Route::post('auth/refresh', [AuthController::class, 'refreshToken'])->name('api.auth.refresh');
    
    // Device/Token management
    Route::get('auth/devices', [AuthController::class, 'getDevices'])->name('api.auth.devices');
    Route::delete('auth/devices/{token_id}', [AuthController::class, 'revokeDevice'])->name('api.auth.revoke-device');
    Route::post('auth/revoke-others', [AuthController::class, 'revokeOtherDevices'])->name('api.auth.revoke-others');
    Route::post('auth/revoke-all', [AuthController::class, 'revokeAll'])->name('api.auth.revoke-all');
});

// Sanctum-protected admin API routes
Route::middleware(['auth:sanctum', 'sanctum.admin'])->prefix('admin')->group(function () {
    
    // Orders API Routes - demonstrate Laravel 12 ability middleware
    Route::get('orders', [OrderApiController::class, 'index'])->middleware('ability:admin:read');
    Route::post('orders', [OrderApiController::class, 'store'])->middleware('abilities:admin:read,admin:write');
    Route::get('orders/{order}', [OrderApiController::class, 'show'])->middleware('ability:admin:read');
    Route::put('orders/{order}', [OrderApiController::class, 'update'])->middleware('abilities:admin:read,admin:write');
    Route::delete('orders/{order}', [OrderApiController::class, 'destroy'])->middleware('abilities:admin:read,admin:write');
    Route::patch('orders/{order}/cancel', [OrderApiController::class, 'cancel'])->name('api.admin.orders.cancel')->middleware('abilities:admin:read,admin:write');
    
    // Users API Routes - demonstrate Laravel 12 ability middleware
    Route::get('users', [UserApiController::class, 'index'])->middleware('ability:admin:read');
    Route::post('users', [UserApiController::class, 'store'])->middleware('abilities:admin:read,admin:write');
    Route::get('users/{user}', [UserApiController::class, 'show'])->middleware('ability:admin:read');
    Route::put('users/{user}', [UserApiController::class, 'update'])->middleware('abilities:admin:read,admin:write');
    Route::delete('users/{user}', [UserApiController::class, 'destroy'])->middleware('abilities:admin:read,admin:write');
    Route::patch('users/{user}/toggle-verification', [UserApiController::class, 'toggleVerification'])->name('api.admin.users.toggle-verification')->middleware('abilities:admin:read,admin:write');
    Route::patch('users/{user}/reset-password', [UserApiController::class, 'resetPassword'])->name('api.admin.users.reset-password')->middleware('abilities:admin:read,admin:write');
    
    // Dashboard data endpoints
    Route::get('dashboard/orders', [OrderApiController::class, 'index'])->name('api.admin.dashboard.orders')->middleware('ability:admin:read');
    Route::get('dashboard/users', [UserApiController::class, 'index'])->name('api.admin.dashboard.users')->middleware('ability:admin:read');
}); 