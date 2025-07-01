<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Requests\OrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class OrderApiController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Display a listing of orders.
     */
    public function index(): JsonResponse
    {
        $orders = $this->orderService->getAllOrders();
        return $this->successResponse($orders);
    }

    /**
     * Store a newly created order.
     */
    public function store(OrderRequest $request): JsonResponse
    {
        $result = $this->orderService->createOrder($request->validated());
        
        if (!$result['success']) {
            return $this->errorResponse($result['message'], 400);
        }

        return $this->successResponse($result['order'], $result['message'], 201);
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order): JsonResponse
    {
        $order->load('product');
        return $this->successResponse((new OrderResource($order))->toDetailedArray());
    }

    /**
     * Update the specified order.
     */
    public function update(OrderRequest $request, Order $order): JsonResponse
    {
        if ($request->isStatusUpdate()) {
            $result = $this->orderService->updateOrderStatus($order, $request->validated()['status']);
        } else {
            // For full updates, you might want to implement this in the service
            $result = ['success' => false, 'message' => 'Full order updates not supported yet'];
        }
        
        if (!$result['success']) {
            return $this->errorResponse($result['message'], 400);
        }

        return $this->successResponse($result['order'], $result['message']);
    }

    /**
     * Remove the specified order.
     */
    public function destroy(Order $order): JsonResponse
    {
        $result = $this->orderService->deleteOrder($order);
        
        if (!$result['success']) {
            return $this->errorResponse($result['message'], 400);
        }

        return $this->successResponse(null, $result['message']);
    }

    /**
     * Cancel an order.
     */
    public function cancel(Order $order): JsonResponse
    {
        $result = $this->orderService->cancelOrder($order);
        
        if (!$result['success']) {
            return $this->errorResponse($result['message'], 400);
        }

        return $this->successResponse($result['order'], 'Order cancelled successfully!');
    }
} 