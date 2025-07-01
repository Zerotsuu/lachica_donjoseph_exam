<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Product;

/*
|--------------------------------------------------------------------------
| Public Routes (Accessible to Everyone)
|--------------------------------------------------------------------------
|
| These routes can be accessed by unauthenticated users (guests).
| This includes the landing page, product viewing, and authentication pages.
|
*/

// Landing Page - Shows featured products
Route::get('/', function () {
    $products = Product::latest()
        ->where('stocks', '>', 0) // Only show products in stock
        ->limit(12) // Limit to 12 featured products on landing
        ->get()
        ->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'formatted_price' => $product->formatted_price,
                'stocks' => $product->stocks,
                'image' => $product->image,
                'image_url' => $product->image_url,
                'description' => $product->description,
                'in_stock' => $product->isInStock(),
            ];
        });

    return Inertia::render('Landing', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'products' => $products,
    ]);
})->name('home');

// Public Product Viewing Routes
Route::prefix('products')->name('products.')->group(function () {
    // List all products (public catalog)
    Route::get('/', function () {
        $products = Product::latest()
            ->where('stocks', '>', 0) // Only show products in stock
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'formatted_price' => $product->formatted_price,
                    'stocks' => $product->stocks,
                    'image' => $product->image,
                    'image_url' => $product->image_url,
                    'description' => $product->description,
                    'in_stock' => $product->isInStock(),
                ];
            });

        return Inertia::render('Products', [
            'products' => $products,
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    })->name('index');

    // View individual product (public view)
    Route::get('/{product}', function (Product $product) {
        // Only show if product is in stock
        if (!$product->isInStock()) {
            abort(404, 'Product not available');
        }

        return Inertia::render('ProductDetail', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'formatted_price' => $product->formatted_price,
                'stocks' => $product->stocks,
                'image' => $product->image,
                'image_url' => $product->image_url,
                'description' => $product->description,
                'in_stock' => $product->isInStock(),
                'created_at' => $product->created_at->format('Y-m-d H:i:s'),
            ],
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    })->name('show');
});

// Authentication Routes (login, register, password reset)
require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Protected Routes (Require Authentication)
|--------------------------------------------------------------------------
|
| These routes require users to be logged in. This includes admin panels,
| user settings, shopping cart, and other user-specific functionality.
|
*/

// Admin Routes (Web + API)
require __DIR__.'/admin.php';

// User Settings Routes  
require __DIR__.'/settings.php';

// Shopping Cart Routes
require __DIR__.'/cart.php';
