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

// API auth routes (for programmatic access)
Route::prefix('api/auth')->group(function () {
    // API Login - returns token directly
    Route::post('login', [AuthController::class, 'login'])->name('api.auth.login');
    
    // Protected API auth routes
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        // Route to get the current user
        Route::get('me', [AuthController::class, 'me'])->name('api.auth.me');
        // Route to revoke all tokens for the current user (still testing)
        Route::post('revoke-all', [AuthController::class, 'revokeAll'])->name('api.auth.revoke-all');
    });
});

// Web logout (for browser sessions)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('logout', [SanctumAuthenticatedSessionController::class, 'destroy'])->name('logout');
});