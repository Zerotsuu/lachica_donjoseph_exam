<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'customer_name' => $this->customer_name,
            'product_name' => $this->product_name,
            'quantity' => $this->quantity,
            'total_amount' => $this->formatted_total,
            'status' => $this->status,
            'status_color' => $this->status_color,
            'can_be_cancelled' => $this->canBeCancelled(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Transform the resource with product details
     */
    public function toDetailedArray(): array
    {
        $data = $this->toArray(request());
        
        if ($this->product) {
            $data['product'] = [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'price' => $this->product->formatted_price,
                'stocks' => $this->product->stocks,
            ];
        }
        
        return $data;
    }

    /**
     * Transform collection of orders
     */
    public static function collection($resource)
    {
        return $resource->map(function ($order) {
            return (new static($order))->toArray(request());
        });
    }
} 