<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Auth\SanctumAuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

// Guest routes (no authentication required)
Route::middleware('guest')->group(function () {
    // Web login pages
    Route::get('login', [SanctumAuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [SanctumAuthenticatedSessionController::class, 'store']);
    
    // Registration
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
    
    // Password reset
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

// API Authentication Routes
Route::prefix('api/auth')->group(function () {
    // Public API Login - returns token directly
    Route::post('login', [AuthController::class, 'login'])->name('api.auth.login');
    
    // Protected API auth routes
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('api.auth.me');
        Route::post('refresh', [AuthController::class, 'refreshToken'])->name('api.auth.refresh');
        
        // Device/Token management
        Route::get('devices', [AuthController::class, 'getDevices'])->name('api.auth.devices');
        Route::delete('devices/{token_id}', [AuthController::class, 'revokeDevice'])->name('api.auth.revoke-device');
        Route::post('revoke-others', [AuthController::class, 'revokeOtherDevices'])->name('api.auth.revoke-others');
        Route::post('revoke-all', [AuthController::class, 'revokeAll'])->name('api.auth.revoke-all');
    });
});

// Web logout (for browser sessions)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('logout', [SanctumAuthenticatedSessionController::class, 'destroy'])->name('logout');
});