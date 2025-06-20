<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'stocks',
        'image',
        'description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stocks' => 'integer',
    ];

    /**
     * Get the orders for the product.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get formatted price with currency symbol.
     */
    public function getFormattedPriceAttribute(): string
    {
        return '₱' . number_format($this->price, 2);
    }

    /**
     * Check if product is in stock.
     */
    public function isInStock(): bool
    {
        return $this->stocks > 0;
    }

    /**
     * Reduce stock when order is placed.
     */
    public function reduceStock(int $quantity): bool
    {
        if ($this->stocks >= $quantity) {
            $this->stocks -= $quantity;
            return $this->save();
        }
        return false;
    }

    /**
     * Get the full URL for the product image.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }
        
        return asset('storage/' . $this->image);
    }

    /**
     * Check if product has an image.
     */
    public function hasImage(): bool
    {
        return !empty($this->image);
    }
}
