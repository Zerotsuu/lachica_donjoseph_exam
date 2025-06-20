<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Laptop Pro',
                'price' => 85000.00,
                'stocks' => 15,
                'description' => 'High-performance laptop with 16GB RAM and 512GB SSD',
            ],
            [
                'name' => 'Wireless Mouse',
                'price' => 1500.00,
                'stocks' => 50,
                'description' => 'Ergonomic wireless mouse with long battery life',
            ],
            [
                'name' => 'Mechanical Keyboard',
                'price' => 3500.00,
                'stocks' => 25,
                'description' => 'RGB mechanical keyboard with blue switches',
            ],
            [
                'name' => 'USB-C Hub',
                'price' => 2800.00,
                'stocks' => 30,
                'description' => '7-in-1 USB-C hub with HDMI, USB 3.0, and SD card reader',
            ],
            [
                'name' => 'Bluetooth Headphones',
                'price' => 4500.00,
                'stocks' => 20,
                'description' => 'Noise-cancelling Bluetooth headphones with 30-hour battery',
            ],
            [
                'name' => 'Smartphone',
                'price' => 25000.00,
                'stocks' => 12,
                'description' => 'Latest smartphone with 128GB storage and dual camera',
            ],
            [
                'name' => 'Tablet',
                'price' => 18000.00,
                'stocks' => 18,
                'description' => '10-inch tablet with stylus support and 256GB storage',
            ],
            [
                'name' => 'Webcam HD',
                'price' => 3200.00,
                'stocks' => 35,
                'description' => '1080p HD webcam with auto-focus and built-in microphone',
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
