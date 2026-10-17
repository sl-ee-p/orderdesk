<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use App\Services\QuoteConversionService;
use App\Services\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SnapshotAndNumberingTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_product_keeps_the_quote_and_order_lines(): void
    {
        $quote = Quote::factory()->accepted()->withItems(2)->create();
        $order = app(QuoteConversionService::class)->convert($quote);

        Product::query()->delete();

        $this->assertSame(2, $quote->items()->count());
        $this->assertSame(2, $order->items()->count());
        $this->assertNull($quote->items()->first()->product_id);
        $this->assertNotEmpty($quote->items()->first()->description);
    }

    public function test_browser_supplied_prices_are_ignored(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create(['unit_price_cents' => 10000]);

        $this->post('/quotes', [
            'customer_id' => Customer::factory()->create()->id,
            'tax_rate' => '0',
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price_cents' => 1, 'unit_price' => '0.01']],
        ])->assertRedirect();

        $this->assertSame(10000, Quote::firstOrFail()->total_cents);
    }

    public function test_document_numbers_are_unique_and_follow_the_id(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        $service = app(QuoteService::class);

        $numbers = collect(range(1, 3))->map(fn () => $service->create([
            'customer_id' => $customer->id,
            'tax_rate_bp' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ], $user)->number);

        $this->assertCount(3, $numbers->unique());
        $this->assertSame(
            Quote::orderBy('id')->pluck('id')->map(fn ($id) => sprintf('Q-%s-%05d', now()->format('Y'), $id))->all(),
            $numbers->all(),
        );
    }
}
