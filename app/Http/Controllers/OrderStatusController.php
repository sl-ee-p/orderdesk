<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderStatusRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;

class OrderStatusController extends Controller
{
    public function __invoke(OrderStatusRequest $request, Order $order, OrderService $orders): RedirectResponse
    {
        $orders->transition($order, $request->status());

        return redirect()->route('orders.show', $order)->with('status', 'Order status updated.');
    }
}
