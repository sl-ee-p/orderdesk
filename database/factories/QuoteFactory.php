<?php

namespace Database\Factories;

use App\Enums\QuoteStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use App\Services\TotalsCalculator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'Q-'.fake()->unique()->numerify('######'),
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'valid_until' => fake()->dateTimeBetween('+7 days', '+45 days'),
            'status' => QuoteStatus::Draft,
            'tax_rate_bp' => fake()->randomElement([0, 700, 1000, 2200]),
            'notes' => fake()->boolean(30) ? fake()->sentence() : null,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => QuoteStatus::Draft]);
    }

    public function sent(): static
    {
        return $this->state(['status' => QuoteStatus::Sent]);
    }

    public function accepted(): static
    {
        return $this->state(['status' => QuoteStatus::Accepted]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => QuoteStatus::Rejected]);
    }

    /**
     * Attach line items (snapshotted from random products) and store matching totals.
     */
    public function withItems(int $count = 3): static
    {
        return $this->afterCreating(function (Quote $quote) use ($count): void {
            $products = Product::query()->inRandomOrder()->limit($count)->get();

            if ($products->count() < $count) {
                $products = $products->concat(Product::factory()->count($count - $products->count())->create());
            }

            foreach ($products as $product) {
                $quantity = fake()->numberBetween(1, 10);

                $quote->items()->create([
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'quantity' => $quantity,
                    'unit_price_cents' => $product->unit_price_cents,
                    'line_total_cents' => $quantity * $product->unit_price_cents,
                ]);
            }

            $quote->update(app(TotalsCalculator::class)->totals($quote->items, $quote->tax_rate_bp));
        });
    }
}
