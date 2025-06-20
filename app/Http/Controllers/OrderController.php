<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
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

        return Inertia::render('Orders', [
            'orders' => $orders
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
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
            return redirect()->back()->with('error', 'Insufficient stock for this product.');
        }

        // Calculate total amount
        $totalAmount = $product->price * $validated['quantity'];

        // Create order
        $order = Order::create([
            'user_id' => auth()->id(),
            'customer_name' => $validated['customer_name'],
            'product_id' => $validated['product_id'],
            'product_name' => $product->name,
            'quantity' => $validated['quantity'],
            'total_amount' => $totalAmount,
            'status' => $validated['status'],
        ]);

        // Reduce stock when order is placed (regardless of initial status)
        // Stock is only restored if order is cancelled later
        $product->reduceStock($validated['quantity']);

        return redirect()->back()->with('success', 'Order created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order): Response
    {
        $order->load('product');

        return Inertia::render('Orders/Show', [
            'order' => [
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
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     * Only allows status updates - quantity and other details cannot be changed after order creation.
     */
    public function update(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['Pending', 'For Delivery', 'Delivered', 'Cancelled'])],
        ]);

        $oldStatus = $order->status;
        $newStatus = $validated['status'];

        // Prevent status change if it's the same
        if ($oldStatus === $newStatus) {
            return redirect()->back()->with('info', 'Order status is already ' . $newStatus . '.');
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
                return redirect()->back()->with('error', 'Insufficient stock to reactivate this order.');
            }
            $product->reduceStock($order->quantity);
        }

        // Update only the status
        $order->update([
            'status' => $newStatus,
        ]);

        return redirect()->back()->with('success', 'Order status updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order): RedirectResponse
    {
        // Add stock back if order was not cancelled
        if ($order->status !== 'Cancelled') {
            $product = $order->product;
            if ($product) {
                $product->increment('stocks', $order->quantity);
            }
        }

        $order->delete();

        return redirect()->back()->with('success', 'Order deleted successfully!');
    }

    /**
     * Cancel an order.
     */
    public function cancel(Order $order): RedirectResponse
    {
        if (!$order->canBeCancelled()) {
            return redirect()->back()->with('error', 'This order cannot be cancelled.');
        }

        // Add stock back
        $product = $order->product;
        if ($product) {
            $product->increment('stocks', $order->quantity);
        }

        $order->update(['status' => 'Cancelled']);

        return redirect()->back()->with('success', 'Order cancelled successfully!');
    }


}
