<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Http\Resources\OrderResource;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Get all orders with transformation
     */
    public function getAllOrders()
    {
        return OrderResource::collection(Order::with('product')->latest()->get());
    }

    /**
     * Create a new order
     */
    public function createOrder(array $data): array
    {
        $product = Product::findOrFail($data['product_id']);

        // Check if there's enough stock
        if ($product->stocks < $data['quantity']) {
            return [
                'success' => false,
                'message' => 'Insufficient stock for this product.',
                'order' => null
            ];
        }

        // Calculate total amount
        $totalAmount = $product->price * $data['quantity'];

        DB::beginTransaction();
        try {
            // Create order
            $order = Order::create([
                'user_id' => auth('sanctum')->id() ?? auth()->id(),
                'customer_name' => $data['customer_name'],
                'product_id' => $data['product_id'],
                'product_name' => $product->name,
                'quantity' => $data['quantity'],
                'total_amount' => $totalAmount,
                'status' => $data['status'],
            ]);

            // Reduce stock when order is placed
            $product->reduceStock($data['quantity']);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Order created successfully!',
                'order' => $order
            ];
        } catch (\Exception $e) {
            DB::rollback();
            return [
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage(),
                'order' => null
            ];
        }
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(Order $order, string $newStatus): array
    {
        $oldStatus = $order->status;

        // Prevent status change if it's the same
        if ($oldStatus === $newStatus) {
            return [
                'success' => false,
                'message' => "Order status is already {$newStatus}.",
                'order' => $order
            ];
        }

        $product = $order->product;
        
        DB::beginTransaction();
        try {
            // Handle stock changes based on status transitions
            if ($oldStatus !== 'Cancelled' && $newStatus === 'Cancelled') {
                // Order is being cancelled: restore stock
                if ($product) {
                    $product->increment('stocks', $order->quantity);
                }
            } elseif ($oldStatus === 'Cancelled' && $newStatus !== 'Cancelled') {
                // Order is being reactivated from cancelled: reduce stock
                if (!$product || $product->stocks < $order->quantity) {
                    DB::rollback();
                    return [
                        'success' => false,
                        'message' => 'Insufficient stock to reactivate this order.',
                        'order' => $order
                    ];
                }
                $product->reduceStock($order->quantity);
            }

            // Update only the status
            $order->update(['status' => $newStatus]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Order status updated successfully!',
                'order' => $order->fresh()
            ];
        } catch (\Exception $e) {
            DB::rollback();
            return [
                'success' => false,
                'message' => 'Failed to update order: ' . $e->getMessage(),
                'order' => $order
            ];
        }
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(Order $order): array
    {
        if (!$order->canBeCancelled()) {
            return [
                'success' => false,
                'message' => 'This order cannot be cancelled.',
                'order' => $order
            ];
        }

        return $this->updateOrderStatus($order, 'Cancelled');
    }

    /**
     * Delete an order
     */
    public function deleteOrder(Order $order): array
    {
        DB::beginTransaction();
        try {
            // Add stock back if order was not cancelled
            if ($order->status !== 'Cancelled') {
                $product = $order->product;
                if ($product) {
                    $product->increment('stocks', $order->quantity);
                }
            }

            $order->delete();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Order deleted successfully!'
            ];
        } catch (\Exception $e) {
            DB::rollback();
            return [
                'success' => false,
                'message' => 'Failed to delete order: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get order by ID
     */
    public function getOrderById(int $id): ?Order
    {
        return Order::with('product')->find($id);
    }
} 