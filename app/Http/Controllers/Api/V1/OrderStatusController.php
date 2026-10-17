<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;

class OrderStatusController extends Controller
{
    public function __invoke(OrderStatusRequest $request, Order $order, OrderService $orders): OrderResource
    {
        $order = $orders->transition($order, $request->status());

        return new OrderResource($order->load(['customer', 'items']));
    }
}
