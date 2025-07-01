<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $isUpdate = $this->getMethod() === 'PUT' || $this->getMethod() === 'PATCH';
        
        // For status updates, only validate status
        if ($isUpdate && $this->has('status') && count($this->all()) === 1) {
            return [
                'status' => ['required', Rule::in(['Pending', 'For Delivery', 'Delivered', 'Cancelled'])],
            ];
        }

        return [
            'customer_name' => 'required|string|max:255',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'status' => ['required', Rule::in(['Pending', 'For Delivery', 'Delivered', 'Cancelled'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'product_id.exists' => 'The selected product does not exist.',
            'quantity.min' => 'Quantity must be at least 1.',
            'status.in' => 'Status must be one of: Pending, For Delivery, Delivered, or Cancelled.',
        ];
    }

    /**
     * Get the order statuses
     */
    public static function getOrderStatuses(): array
    {
        return ['Pending', 'For Delivery', 'Delivered', 'Cancelled'];
    }

    /**
     * Check if this is a status-only update
     */
    public function isStatusUpdate(): bool
    {
        return $this->has('status') && count($this->all()) === 1;
    }
} 