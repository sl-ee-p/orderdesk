<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\TotalsCalculator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Mirror the services: start with a unique placeholder, then derive the real number from the id.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Order $model): void {
            $model->update(['number' => sprintf('O-%s-%05d', now()->format('Y'), $model->id)]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => (string) fake()->unique()->uuid(),
            'customer_id' => Customer::factory(),
            'status' => OrderStatus::Draft,
            'tax_rate_bp' => fake()->randomElement([0, 700, 1000, 2200]),
            'notes' => fake()->boolean(30) ? fake()->sentence() : null,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => OrderStatus::Draft]);
    }

    public function confirmed(): static
    {
        return $this->state(['status' => OrderStatus::Confirmed, 'confirmed_at' => now()]);
    }

    public function shipped(): static
    {
        return $this->state(['status' => OrderStatus::Shipped, 'confirmed_at' => now()->subDays(2), 'shipped_at' => now()]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => OrderStatus::Cancelled, 'cancelled_at' => now()]);
    }

    /**
     * Attach line items (snapshotted from random products) and store matching totals.
     */
    public function withItems(int $count = 3): static
    {
        return $this->afterCreating(function (Order $order) use ($count): void {
            $products = Product::query()->inRandomOrder()->limit($count)->get();

            if ($products->count() < $count) {
                $products = $products->concat(Product::factory()->count($count - $products->count())->create());
            }

            foreach ($products as $product) {
                $quantity = fake()->numberBetween(1, 10);

                $order->items()->create([
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'quantity' => $quantity,
                    'unit_price_cents' => $product->unit_price_cents,
                    'line_total_cents' => $quantity * $product->unit_price_cents,
                ]);
            }

            $order->update(app(TotalsCalculator::class)->totals($order->items, $order->tax_rate_bp));
        });
    }
}
