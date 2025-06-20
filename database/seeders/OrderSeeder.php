<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();
        
        if ($products->isEmpty()) {
            return; // No products available
        }

        $customers = [
            'John Doe',
            'Jane Smith', 
            'Mike Johnson',
            'Sarah Wilson',
            'David Brown',
            'Emma Davis',
            'James Miller',
            'Lisa Garcia',
        ];

        $statuses = ['Pending', 'For Delivery', 'Delivered', 'Cancelled'];

        $orders = [
            [
                'customer_name' => 'John Doe',
                'product' => $products->where('name', 'Laptop Pro')->first(),
                'quantity' => 2,
                'status' => 'Delivered',
            ],
            [
                'customer_name' => 'Jane Smith',
                'product' => $products->where('name', 'Wireless Mouse')->first(),
                'quantity' => 3,
                'status' => 'Pending',
            ],
            [
                'customer_name' => 'Mike Johnson',
                'product' => $products->where('name', 'Mechanical Keyboard')->first(),
                'quantity' => 1,
                'status' => 'For Delivery',
            ],
            [
                'customer_name' => 'Sarah Wilson',
                'product' => $products->where('name', 'Smartphone')->first(),
                'quantity' => 1,
                'status' => 'Delivered',
            ],
            [
                'customer_name' => 'David Brown',
                'product' => $products->where('name', 'Bluetooth Headphones')->first(),
                'quantity' => 2,
                'status' => 'Pending',
            ],
            [
                'customer_name' => 'Emma Davis',
                'product' => $products->where('name', 'Tablet')->first(),
                'quantity' => 1,
                'status' => 'Cancelled',
            ],
            [
                'customer_name' => 'James Miller',
                'product' => $products->where('name', 'USB-C Hub')->first(),
                'quantity' => 4,
                'status' => 'For Delivery',
            ],
            [
                'customer_name' => 'Lisa Garcia',
                'product' => $products->where('name', 'Webcam HD')->first(),
                'quantity' => 2,
                'status' => 'Delivered',
            ],
        ];

        foreach ($orders as $orderData) {
            $product = $orderData['product'];
            
            if (!$product) {
                continue; // Skip if product doesn't exist
            }
            
            $totalAmount = $product->price * $orderData['quantity'];
            
            Order::create([
                'customer_name' => $orderData['customer_name'],
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $orderData['quantity'],
                'total_amount' => $totalAmount,
                'status' => $orderData['status'],
            ]);
            
            // Reduce stock if order is not cancelled
            if ($orderData['status'] !== 'Cancelled') {
                $product->reduceStock($orderData['quantity']);
            }
        }
    }
}
