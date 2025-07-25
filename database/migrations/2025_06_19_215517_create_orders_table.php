<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('product_name'); // Store product name for historical purposes
            $table->integer('quantity');
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['Pending', 'For Delivery', 'Delivered', 'Cancelled'])->default('Pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
