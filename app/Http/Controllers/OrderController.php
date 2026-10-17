<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        return view('orders.index', [
            'orders' => Order::query()->with('customer')->latest('id')->paginate(15),
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['customer', 'items', 'quote']);

        return view('orders.show', ['order' => $order]);
    }
}
