<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Public & Authenticated
|--------------------------------------------------------------------------
| 
| Semantic organization of API endpoints following RESTful conventions
| and Laravel best practices for API design.
|
*/

// Public API Routes (no authentication required)
Route::prefix('v1')->name('api.v1.')->group(function () {
    
    // Public catalog endpoints
    Route::prefix('catalog')->name('catalog.')->group(function () {
        Route::get('products', function () {
            // Public product listing - could move to ProductController::public
            return response()->json(['message' => 'Public product catalog endpoint']);
        })->name('products.index');
        
        Route::get('products/{product}', function ($productId) {
            // Public product details
            return response()->json(['message' => "Public product {$productId} details"]);
        })->name('products.show');
    });
    
    // Health check and system status
    Route::get('health', function () {
        return response()->json([
            'status' => 'ok',
            'service' => config('app.name'),
            'version' => '1.0.0',
            'timestamp' => now()->toISOString()
        ]);
    })->name('health');
});

// Authenticated API Routes
Route::prefix('v1')->name('api.v1.')->middleware('auth:sanctum')->group(function () {
    
    // User Context Routes
    Route::prefix('user')->name('user.')->group(function () {
        // Current user information
        Route::get('profile', function (Request $request) {
            return response()->json([
                'success' => true,
                'data' => $request->user()
            ]);
        })->name('profile');
        
        // User-specific resources
        Route::get('orders', function () {
            // User's order history - could move to OrderController::userOrders
            return response()->json(['message' => 'User orders endpoint']);
        })->name('orders');
        
        Route::get('cart', function () {
            // User's cart - could move to CartController::show
            return response()->json(['message' => 'User cart endpoint']);
        })->name('cart');
    });
    
    // Admin-only routes will be loaded from admin.php
    // Customer-facing routes will be loaded from customer.php (if created)
});

/*
|--------------------------------------------------------------------------
| Legacy Route Support
|--------------------------------------------------------------------------
| 
| Maintain backward compatibility while transitioning to semantic structure
|
*/

// Legacy user endpoint (maintained for backward compatibility)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->name('api.user');
});
