<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Order;
use App\Models\Quote;
use App\Models\User;
use App\Services\QuoteConversionService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QuoteConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepted_quote_becomes_a_draft_order_with_copied_items_and_totals(): void
    {
        $quote = Quote::factory()->accepted()->withItems(3)->create(['notes' => 'Deliver to dock 4']);

        $order = app(QuoteConversionService::class)->convert($quote)->load('items');

        $this->assertMatchesRegularExpression('/^O-\d{4}-\d{5}$/', $order->number);
        $this->assertSame(OrderStatus::Draft, $order->status);
        $this->assertSame($quote->id, $order->quote_id);
        $this->assertSame($quote->customer_id, $order->customer_id);
        $this->assertSame('Deliver to dock 4', $order->notes);
        $this->assertSame(
            [$quote->tax_rate_bp, $quote->subtotal_cents, $quote->tax_cents, $quote->total_cents],
            [$order->tax_rate_bp, $order->subtotal_cents, $order->tax_cents, $order->total_cents],
        );
        $this->assertEquals(
            $quote->items->map->only(['description', 'quantity', 'unit_price_cents', 'line_total_cents'])->all(),
            $order->items->map->only(['description', 'quantity', 'unit_price_cents', 'line_total_cents'])->all(),
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonAcceptedStatuses(): array
    {
        return ['draft' => ['draft'], 'sent' => ['sent'], 'rejected' => ['rejected']];
    }

    #[DataProvider('nonAcceptedStatuses')]
    public function test_only_accepted_quotes_can_be_converted(string $state): void
    {
        $quote = Quote::factory()->{$state}()->withItems()->create();

        $this->expectException(DomainRuleViolation::class);

        try {
            app(QuoteConversionService::class)->convert($quote);
        } finally {
            $this->assertDatabaseCount('orders', 0);
        }
    }

    public function test_a_quote_cannot_be_converted_twice(): void
    {
        $quote = Quote::factory()->accepted()->withItems()->create();
        $service = app(QuoteConversionService::class);
        $service->convert($quote);

        try {
            $service->convert($quote);
            $this->fail('Second conversion should have been refused.');
        } catch (DomainRuleViolation $e) {
            $this->assertSame('This quote has already been converted to an order.', $e->getMessage());
        }

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_database_itself_rejects_two_orders_for_one_quote(): void
    {
        $quote = Quote::factory()->accepted()->create();
        Order::factory()->create(['quote_id' => $quote->id]);

        $this->expectException(UniqueConstraintViolationException::class);
        Order::factory()->create(['quote_id' => $quote->id]);
    }

    public function test_convert_button_creates_the_order_in_the_web_ui(): void
    {
        $this->actingAs(User::factory()->create());
        $quote = Quote::factory()->accepted()->withItems()->create();

        $this->get("/quotes/{$quote->id}")->assertSee('Convert to order');

        $response = $this->post("/quotes/{$quote->id}/convert");

        $order = Order::firstOrFail();
        $response->assertRedirect("/orders/{$order->id}");
        $this->get("/quotes/{$quote->id}")->assertDontSee('Convert to order')->assertSee($order->number);
    }

    public function test_converting_a_draft_quote_over_http_shows_an_error(): void
    {
        $this->actingAs(User::factory()->create());
        $quote = Quote::factory()->withItems()->create();

        $this->post("/quotes/{$quote->id}/convert")
            ->assertSessionHas('error', 'Only accepted quotes can be converted to an order.');
        $this->assertDatabaseCount('orders', 0);
    }
}
