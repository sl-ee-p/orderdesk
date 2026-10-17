<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);
        $unitPrice = fake()->numberBetween(500, 100000);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'description' => ucfirst(fake()->words(3, true)),
            'quantity' => $quantity,
            'unit_price_cents' => $unitPrice,
            'line_total_cents' => $quantity * $unitPrice,
        ];
    }
}
