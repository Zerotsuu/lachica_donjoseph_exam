<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    /**
     * Get the user's cart with items
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $cart = $user->cart()->with(['cartItems.product'])->first();
        
        if (!$cart) {
            return response()->json([
                'cart_items' => [],
                'total' => 0,
                'formatted_total' => '₱0.00',
                'item_count' => 0
            ]);
        }

        $cartItems = $cart->cartItems->map(function ($item) {
            return [
                'id' => $item->id,
                'product' => [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'price' => $item->product->price,
                    'formatted_price' => $item->product->formatted_price,
                    'image' => $item->product->image,
                    'image_url' => $item->product->image_url,
                ],
                'quantity' => $item->quantity,
                'total' => $item->total,
                'formatted_total' => $item->formatted_total,
            ];
        });

        return response()->json([
            'cart_items' => $cartItems,
            'total' => $cart->total,
            'formatted_total' => $cart->formatted_total,
            'item_count' => $cart->item_count
        ]);
    }

    /**
     * Add item to cart
     */
    public function addItem(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Simple validation first
            if (!$request->has('product_id') || !$request->has('quantity')) {
                return response()->json(['error' => 'Missing required fields'], 400);
            }

            $productId = $request->input('product_id');
            $quantity = $request->input('quantity');

            if (!is_numeric($productId) || !is_numeric($quantity) || $quantity < 1) {
                return response()->json(['error' => 'Invalid input'], 400);
            }

            $product = Product::find($productId);
            if (!$product) {
                return response()->json(['error' => 'Product not found'], 404);
            }
            
            // Check if product has enough stock
            if ($product->stocks < $quantity) {
                return response()->json(['error' => 'Not enough stock available'], 400);
            }

            $cart = $user->getOrCreateCart();
            
            // Check if item already exists in cart
            $cartItem = $cart->cartItems()->where('product_id', $product->id)->first();
            
            if ($cartItem) {
                // Update existing item
                $newQuantity = $cartItem->quantity + $quantity;
                
                // Check stock again for total quantity
                if ($product->stocks < $newQuantity) {
                    return response()->json(['error' => 'Not enough stock for total quantity'], 400);
                }
                
                $cartItem->update(['quantity' => $newQuantity]);
            } else {
                // Create new cart item
                $cart->cartItems()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $product->price
                ]);
            }

            return response()->json(['message' => 'Item added to cart successfully']);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateQuantity(Request $request, CartItem $cartItem): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $cartItem->cart->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'quantity' => 'required|integer|min:1|max:10'
        ]);

        // Check stock availability
        if ($cartItem->product->stocks < $request->quantity) {
            return response()->json(['error' => 'Not enough stock available'], 400);
        }

        $cartItem->update(['quantity' => $request->quantity]);

        return response()->json(['message' => 'Cart updated successfully']);
    }

    /**
     * Remove item from cart
     */
    public function removeItem(CartItem $cartItem): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $cartItem->cart->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $cartItem->delete();

        return response()->json(['message' => 'Item removed from cart']);
    }

    /**
     * Clear entire cart
     */
    public function clear(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $cart = $user->cart;
        if ($cart) {
            $cart->cartItems()->delete();
        }

        return response()->json(['message' => 'Cart cleared successfully']);
    }

    /**
     * Place order from cart items
     */
    public function placeOrder(Request $request): JsonResponse
    {
        try {
            \Log::info('Place order request started');
            
            $user = Auth::user();
            if (!$user) {
                \Log::warning('Unauthorized place order attempt');
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            \Log::info('User authenticated', ['user_id' => $user->id]);

            $cart = $user->cart()->with('cartItems.product')->first();
            
            \Log::info('Cart retrieved', ['cart' => $cart ? $cart->id : 'null', 'items_count' => $cart ? $cart->cartItems->count() : 0]);
            
            if (!$cart || $cart->cartItems->isEmpty()) {
                \Log::warning('Cart is empty for user', ['user_id' => $user->id]);
                return response()->json(['error' => 'Cart is empty'], 400);
            }

            // Validate stock availability for all items
            foreach ($cart->cartItems as $cartItem) {
                if ($cartItem->product->stocks < $cartItem->quantity) {
                    return response()->json([
                        'error' => "Not enough stock for {$cartItem->product->name}. Available: {$cartItem->product->stocks}, Requested: {$cartItem->quantity}"
                    ], 400);
                }
            }

            DB::beginTransaction();

            try {
                $orders = [];
                $totalAmount = 0;

                // Create one order per cart item (working with existing table structure)
                foreach ($cart->cartItems as $cartItem) {
                    $itemTotal = $cartItem->quantity * $cartItem->price;
                    $totalAmount += $itemTotal;

                    // Create order
                    $order = Order::create([
                        'user_id' => $user->id,
                        'customer_name' => $user->name,
                        'product_id' => $cartItem->product_id,
                        'product_name' => $cartItem->product->name,
                        'quantity' => $cartItem->quantity,
                        'total_amount' => $itemTotal,
                        'status' => 'Pending'
                    ]);

                    $orders[] = $order;

                    // Reduce product stock
                    $cartItem->product->reduceStock($cartItem->quantity);
                }

                // Clear the cart
                $cart->cartItems()->delete();

                DB::commit();

                return response()->json([
                    'message' => 'Order placed successfully',
                    'orders_count' => count($orders),
                    'total_amount' => $totalAmount,
                    'formatted_total' => '₱' . number_format($totalAmount, 2),
                    'orders' => array_map(function($order) {
                        return [
                            'id' => $order->id,
                            'product_name' => $order->product_name,
                            'quantity' => $order->quantity,
                            'total_amount' => $order->total_amount,
                            'formatted_total' => $order->formatted_total,
                            'status' => $order->status,
                            'created_at' => $order->created_at
                        ];
                    }, $orders)
                ]);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Place order failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
            return response()->json(['error' => 'Failed to place order: ' . $e->getMessage()], 500);
        }
    }
}
