<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Product;
use App\Models\Order;
use App\Models\User;

class AdminDashboardController extends Controller
{
    /**
     * Display the products management page.
     */
    public function products(): Response
    {
        $products = Product::orderBy('created_at', 'desc')->get();
        
        return Inertia::render('Dashboard', [
            'products' => $products
        ]);
    }

    /**
     * Display the orders management page.
     */
    public function orders(): Response
    {
        $orders = Order::with('product')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return Inertia::render('Orders', [
            'orders' => $orders
        ]);
    }

    /**
     * Display the users management page.
     */
    public function users(): Response
    {
        $users = User::where('id', '!=', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();
            
        return Inertia::render('Users', [
            'users' => $users
        ]);
    }
} 