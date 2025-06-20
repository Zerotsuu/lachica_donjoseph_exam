<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create();
        
        // Create a test product
        $this->product = Product::create([
            'name' => 'Test Product',
            'description' => 'A test product',
            'price' => 100.00,
            'stocks' => 10,
            'image' => null
        ]);
    }

    public function test_user_can_view_empty_cart()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/cart');

        $response->assertStatus(200)
            ->assertJson([
                'cart_items' => [],
                'total' => 0,
                'formatted_total' => '₱0.00',
                'item_count' => 0
            ]);
    }

    public function test_user_can_add_item_to_cart()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/cart/add', [
                'product_id' => $this->product->id,
                'quantity' => 2
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Item added to cart successfully'
            ]);

        // Verify item was added to database
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => 100.00
        ]);
    }

    public function test_guest_cannot_add_item_to_cart()
    {
        $response = $this->postJson('/cart/add', [
            'product_id' => $this->product->id,
            'quantity' => 2
        ]);

        $response->assertStatus(401);
    }

    public function test_cannot_add_more_items_than_stock()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/cart/add', [
                'product_id' => $this->product->id,
                'quantity' => 15 // More than available stock (10)
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Not enough stock available'
            ]);
    }

    public function test_adding_same_product_updates_quantity()
    {
        // Add product first time
        $this->actingAs($this->user)
            ->postJson('/cart/add', [
                'product_id' => $this->product->id,
                'quantity' => 2
            ]);

        // Add same product again
        $response = $this->actingAs($this->user)
            ->postJson('/cart/add', [
                'product_id' => $this->product->id,
                'quantity' => 3
            ]);

        $response->assertStatus(200);

        // Verify quantity was updated, not duplicated
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
            'quantity' => 5 // 2 + 3
        ]);

        // Ensure only one cart item exists for this product
        $cartItemCount = CartItem::where('product_id', $this->product->id)->count();
        $this->assertEquals(1, $cartItemCount);
    }

    public function test_user_can_view_cart_with_items()
    {
        // Add item to cart first
        $cart = $this->user->getOrCreateCart();
        $cart->cartItems()->create([
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => $this->product->price
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/cart');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'cart_items' => [
                    '*' => [
                        'id',
                        'product' => [
                            'id',
                            'name',
                            'price',
                            'formatted_price'
                        ],
                        'quantity',
                        'total',
                        'formatted_total'
                    ]
                ],
                'total',
                'formatted_total',
                'item_count'
            ]);
    }

    public function test_cart_models_work_correctly()
    {
        // Test that the User->Cart relationship works
        $cart = $this->user->getOrCreateCart();
        $this->assertInstanceOf(Cart::class, $cart);
        $this->assertEquals($this->user->id, $cart->user_id);

        // Test that calling getOrCreateCart again returns the same cart
        $sameCart = $this->user->getOrCreateCart();
        $this->assertEquals($cart->id, $sameCart->id);
    }

    public function test_user_can_place_order_from_cart()
    {
        // Add items to cart first
        $cart = $this->user->getOrCreateCart();
        $cart->cartItems()->create([
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => $this->product->price
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/cart/place-order');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'orders_count',
                'total_amount',
                'formatted_total',
                'orders'
            ]);

        // Verify order was created
        $this->assertDatabaseHas('orders', [
            'customer_name' => $this->user->name,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'status' => 'Pending'
        ]);

        // Verify cart was cleared
        $this->assertEquals(0, $cart->fresh()->cartItems()->count());

        // Verify stock was reduced
        $this->assertEquals(8, $this->product->fresh()->stocks); // 10 - 2 = 8
    }

    public function test_cannot_place_order_with_empty_cart()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/cart/place-order');

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Cart is empty'
            ]);
    }

    public function test_cannot_place_order_with_insufficient_stock()
    {
        // Update product to have only 1 stock
        $this->product->update(['stocks' => 1]);

        // Add 2 items to cart (more than available stock)
        $cart = $this->user->getOrCreateCart();
        $cart->cartItems()->create([
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => $this->product->price
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/cart/place-order');

        $response->assertStatus(400)
            ->assertJsonPath('error', 'Not enough stock for Test Product. Available: 1, Requested: 2');
    }
}
