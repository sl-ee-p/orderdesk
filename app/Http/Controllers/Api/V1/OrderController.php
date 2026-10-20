<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends ApiController
{
    /**
     * Optional filters: ?status= and ?customer_id=.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $this->documentFilters($request, OrderStatus::class);

        $orders = Order::query()
            ->with('customer')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['customer_id'] ?? null, fn ($query, $id) => $query->where('customer_id', $id))
            ->latest('id')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return OrderResource::collection($orders);
    }

    public function show(Order $order): OrderResource
    {
        return new OrderResource($order->load(['customer', 'items']));
    }
}
