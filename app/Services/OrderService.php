<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function transition(Order $order, OrderStatus $target): Order
    {
        return DB::transaction(function () use ($order, $target): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $order->status->canTransitionTo($target)) {
                throw DomainRuleViolation::orderTransition($order->status, $target);
            }

            $order->update([
                'status' => $target,
                ...match ($target) {
                    OrderStatus::Confirmed => ['confirmed_at' => now()],
                    OrderStatus::Shipped => ['shipped_at' => now()],
                    OrderStatus::Cancelled => ['cancelled_at' => now()],
                    OrderStatus::Draft => [],
                },
            ]);

            return $order;
        });
    }
}
