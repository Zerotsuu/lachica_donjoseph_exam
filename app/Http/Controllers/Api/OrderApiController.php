<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class OrderApiController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(): JsonResponse
    {
        $orders = Order::with('product')->latest()->get()->map(function ($order) {
            return [
                'id' => $order->id,
                'customer_name' => $order->customer_name,
                'product_name' => $order->product_name,
                'quantity' => $order->quantity,
                'total_amount' => $order->formatted_total,
                'status' => $order->status,
                'status_color' => $order->status_color,
                'can_be_cancelled' => $order->canBeCancelled(),
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Store a newly created order.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'status' => ['required', Rule::in(['Pending', 'For Delivery', 'Delivered', 'Cancelled'])],
        ]);

        $product = Product::findOrFail($validated['product_id']);

        // Check if there's enough stock
        if ($product->stocks < $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock for this product.'
            ], 400);
        }

        // Calculate total amount
        $totalAmount = $product->price * $validated['quantity'];

        // Create order
        $order = Order::create([
            'user_id' => auth('sanctum')->id(),
            'customer_name' => $validated['customer_name'],
            'product_id' => $validated['product_id'],
            'product_name' => $product->name,
            'quantity' => $validated['quantity'],
            'total_amount' => $totalAmount,
            'status' => $validated['status'],
        ]);

        // Reduce stock when order is placed
        $product->reduceStock($validated['quantity']);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully!',
            'data' => $order
        ], 201);
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order): JsonResponse
    {
        $order->load('product');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'customer_name' => $order->customer_name,
                'product_name' => $order->product_name,
                'quantity' => $order->quantity,
                'total_amount' => $order->formatted_total,
                'status' => $order->status,
                'status_color' => $order->status_color,
                'can_be_cancelled' => $order->canBeCancelled(),
                'product' => $order->product ? [
                    'id' => $order->product->id,
                    'name' => $order->product->name,
                    'price' => $order->product->formatted_price,
                    'stocks' => $order->product->stocks,
                ] : null,
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * Update the specified order.
     */
    public function update(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['Pending', 'For Delivery', 'Delivered', 'Cancelled'])],
        ]);

        $oldStatus = $order->status;
        $newStatus = $validated['status'];

        // Prevent status change if it's the same
        if ($oldStatus === $newStatus) {
            return response()->json([
                'success' => false,
                'message' => 'Order status is already ' . $newStatus . '.'
            ], 400);
        }

        $product = $order->product;
        
        // Handle stock changes based on status transitions
        if ($oldStatus !== 'Cancelled' && $newStatus === 'Cancelled') {
            // Order is being cancelled: restore stock
            if ($product) {
                $product->increment('stocks', $order->quantity);
            }
        } elseif ($oldStatus === 'Cancelled' && $newStatus !== 'Cancelled') {
            // Order is being reactivated from cancelled: reduce stock
            if (!$product || $product->stocks < $order->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock to reactivate this order.'
                ], 400);
            }
            $product->reduceStock($order->quantity);
        }

        // Update only the status
        $order->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully!',
            'data' => $order
        ]);
    }

    /**
     * Remove the specified order.
     */
    public function destroy(Order $order): JsonResponse
    {
        // Add stock back if order was not cancelled
        if ($order->status !== 'Cancelled') {
            $product = $order->product;
            if ($product) {
                $product->increment('stocks', $order->quantity);
            }
        }

        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully!'
        ]);
    }

    /**
     * Cancel an order.
     */
    public function cancel(Order $order): JsonResponse
    {
        if (!$order->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'This order cannot be cancelled.'
            ], 400);
        }

        // Add stock back
        $product = $order->product;
        if ($product) {
            $product->increment('stocks', $order->quantity);
        }

        $order->update(['status' => 'Cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully!',
            'data' => $order
        ]);
    }
} 