<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use App\Services\QuoteConversionService;
use App\Services\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Multi-step operations must be all-or-nothing: if a later step fails, nothing is saved.
 */
class TransactionSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        OrderItem::flushEventListeners();
        QuoteItem::flushEventListeners();

        parent::tearDown();
    }

    public function test_failed_conversion_leaves_no_half_built_order(): void
    {
        $quote = Quote::factory()->accepted()->withItems(2)->create();
        OrderItem::creating(fn () => throw new RuntimeException('boom'));

        try {
            app(QuoteConversionService::class)->convert($quote);
            $this->fail('The simulated failure should have surfaced.');
        } catch (RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }

    public function test_failed_quote_creation_leaves_no_quote(): void
    {
        $product = Product::factory()->create();
        $customer = Customer::factory()->create();
        $user = User::factory()->create();
        QuoteItem::creating(fn () => throw new RuntimeException('boom'));

        try {
            app(QuoteService::class)->create([
                'customer_id' => $customer->id,
                'tax_rate_bp' => 0,
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
            ], $user);
            $this->fail('The simulated failure should have surfaced.');
        } catch (RuntimeException) {
            // expected
        }

        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_failed_quote_update_keeps_the_old_items_and_totals(): void
    {
        $quote = Quote::factory()->withItems(2)->create();
        $before = [$quote->items()->count(), $quote->fresh()->total_cents];
        $product = Product::factory()->create();
        QuoteItem::creating(fn () => throw new RuntimeException('boom'));

        try {
            app(QuoteService::class)->update($quote, [
                'customer_id' => $quote->customer_id,
                'tax_rate_bp' => 0,
                'items' => [['product_id' => $product->id, 'quantity' => 9]],
            ]);
            $this->fail('The simulated failure should have surfaced.');
        } catch (RuntimeException) {
            // expected
        }

        $this->assertSame($before, [$quote->items()->count(), $quote->fresh()->total_cents]);
    }
}
