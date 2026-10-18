<?php

namespace Tests\Feature;

use App\Enums\QuoteStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use App\Services\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Customer $customer, Product $product, int $quantity = 2): array
    {
        return [
            'customer_id' => $customer->id,
            'tax_rate' => '22',
            'items' => [['product_id' => $product->id, 'quantity' => $quantity]],
        ];
    }

    public function test_quote_is_created_with_snapshotted_items_and_totals(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['name' => 'Desk', 'unit_price_cents' => 10000]);

        $this->post('/quotes', $this->payload($customer, $product))->assertRedirect();

        $quote = Quote::with('items')->firstOrFail();
        $this->assertMatchesRegularExpression('/^Q-\d{4}-\d{5}$/', $quote->number);
        $this->assertSame(QuoteStatus::Draft, $quote->status);
        $this->assertSame($this->user->id, $quote->user_id);
        $this->assertSame(2200, $quote->tax_rate_bp);
        $this->assertSame([20000, 4400, 24400], [$quote->subtotal_cents, $quote->tax_cents, $quote->total_cents]);
        $this->assertSame('Desk', $quote->items->first()->description);
    }

    public function test_item_prices_do_not_change_when_the_product_price_changes(): void
    {
        $product = Product::factory()->create(['unit_price_cents' => 10000]);
        $this->post('/quotes', $this->payload(Customer::factory()->create(), $product));

        $product->update(['unit_price_cents' => 99999]);

        $this->assertSame(10000, Quote::firstOrFail()->items()->first()->unit_price_cents);
    }

    public function test_validation_requires_items_and_active_products(): void
    {
        $customer = Customer::factory()->create();
        $inactive = Product::factory()->inactive()->create();

        $this->post('/quotes', ['customer_id' => $customer->id, 'tax_rate' => '22'])
            ->assertSessionHasErrors('items');
        $this->post('/quotes', $this->payload($customer, $inactive))
            ->assertSessionHasErrors('items.0.product_id');
        $this->post('/quotes', [...$this->payload($customer, Product::factory()->create()), 'tax_rate' => '150'])
            ->assertSessionHasErrors('tax_rate');

        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_service_refuses_products_that_became_unavailable_after_validation(): void
    {
        $customer = Customer::factory()->create();
        $active = Product::factory()->create();
        $inactive = Product::factory()->inactive()->create();
        $quotes = app(QuoteService::class);

        foreach ([$inactive->id, 999999] as $productId) {
            try {
                $quotes->create([
                    'customer_id' => $customer->id,
                    'tax_rate_bp' => 2200,
                    'items' => [['product_id' => $active->id, 'quantity' => 1], ['product_id' => $productId, 'quantity' => 1]],
                ], $this->user);
                $this->fail('An unavailable product should have been refused.');
            } catch (DomainRuleViolation $e) {
                $this->assertSame(DomainRuleViolation::productUnavailable()->getMessage(), $e->getMessage());
            }
        }

        $this->assertDatabaseCount('quotes', 0);
        $this->assertDatabaseCount('quote_items', 0);
    }

    public function test_malformed_tax_rates_are_validation_errors(): void
    {
        $payload = $this->payload(Customer::factory()->create(), Product::factory()->create());

        $this->post('/quotes', [...$payload, 'tax_rate' => ['22']])->assertSessionHasErrors('tax_rate');
        $this->post('/quotes', [...$payload, 'tax_rate' => '99999999999999999999'])->assertSessionHasErrors('tax_rate');
        $this->post('/quotes', [...$payload, 'tax_rate' => '22,5'])->assertSessionHasErrors('tax_rate');

        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_draft_quote_can_be_edited_and_items_are_replaced(): void
    {
        $quote = Quote::factory()->withItems(3)->create();
        $product = Product::factory()->create(['unit_price_cents' => 1000]);

        $this->put("/quotes/{$quote->id}", $this->payload($quote->customer, $product, 3))
            ->assertRedirect("/quotes/{$quote->id}");

        $quote->refresh();
        $this->assertCount(1, $quote->items);
        $this->assertSame(3000, $quote->subtotal_cents);
    }

    public function test_only_draft_quotes_can_be_edited_or_deleted(): void
    {
        $quote = Quote::factory()->sent()->create();
        $product = Product::factory()->create();

        $this->get("/quotes/{$quote->id}/edit")->assertRedirect("/quotes/{$quote->id}");
        $this->put("/quotes/{$quote->id}", $this->payload($quote->customer, $product))->assertSessionHas('error');
        $this->delete("/quotes/{$quote->id}")->assertSessionHas('error');

        $this->assertModelExists($quote);
    }

    public function test_draft_quote_can_be_deleted(): void
    {
        $quote = Quote::factory()->withItems()->create();

        $this->delete("/quotes/{$quote->id}")->assertRedirect('/quotes');
        $this->assertModelMissing($quote);
    }

    public function test_quote_moves_through_the_status_workflow(): void
    {
        $quote = Quote::factory()->withItems()->create();

        $this->post("/quotes/{$quote->id}/status", ['status' => 'sent'])->assertSessionHas('status');
        $this->post("/quotes/{$quote->id}/status", ['status' => 'accepted'])->assertSessionHas('status');

        $this->assertSame(QuoteStatus::Accepted, $quote->fresh()->status);
    }

    public function test_illegal_status_change_is_refused_with_a_message(): void
    {
        $quote = Quote::factory()->create();

        $this->post("/quotes/{$quote->id}/status", ['status' => 'accepted'])
            ->assertSessionHas('error', 'A draft quote cannot become accepted.');
        $this->post("/quotes/{$quote->id}/status", ['status' => 'bogus'])
            ->assertSessionHasErrors('status');

        $this->assertSame(QuoteStatus::Draft, $quote->fresh()->status);
    }

    public function test_pages_render(): void
    {
        Product::factory()->count(2)->create();
        $quote = Quote::factory()->withItems()->create();

        $this->get('/quotes')->assertOk()->assertSee($quote->number);
        $this->get('/quotes/create')->assertOk();
        $this->get("/quotes/{$quote->id}")->assertOk()->assertSee('Mark as sent');
        $this->get("/quotes/{$quote->id}/edit")->assertOk();
    }
}
