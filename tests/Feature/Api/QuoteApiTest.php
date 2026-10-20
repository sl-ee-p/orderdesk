<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuoteApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(?Customer $customer = null, ?Product $product = null): array
    {
        return [
            'customer_id' => ($customer ?? Customer::factory()->create())->id,
            'tax_rate_bp' => 2200,
            'items' => [['product_id' => ($product ?? Product::factory()->create(['unit_price_cents' => 10000]))->id, 'quantity' => 2]],
        ];
    }

    public function test_quote_is_created_with_totals_and_items(): void
    {
        $this->postJson('/api/v1/quotes', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.subtotal_cents', 20000)
            ->assertJsonPath('data.tax_cents', 4400)
            ->assertJsonPath('data.total_cents', 24400)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.customer.id', Quote::firstOrFail()->customer_id);

        $this->assertSame($this->user->id, Quote::firstOrFail()->user_id);
    }

    public function test_validation_errors(): void
    {
        $this->postJson('/api/v1/quotes', ['customer_id' => 999, 'tax_rate_bp' => 20000, 'items' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_id', 'tax_rate_bp', 'items']);

        $inactive = Product::factory()->inactive()->create();
        $this->postJson('/api/v1/quotes', $this->payload(product: $inactive))
            ->assertJsonValidationErrors('items.0.product_id');
    }

    public function test_largest_allowed_quote_is_totalled_exactly(): void
    {
        $product = Product::factory()->create(['unit_price_cents' => 4294967295]);
        $items = array_fill(0, 100, ['product_id' => $product->id, 'quantity' => 100000]);

        $this->postJson('/api/v1/quotes', [...$this->payload(), 'tax_rate_bp' => 1950, 'items' => $items])
            ->assertCreated()
            ->assertJsonPath('data.subtotal_cents', 42949672950000000)
            ->assertJsonPath('data.tax_cents', 8375186225250000)
            ->assertJsonPath('data.total_cents', 51324859175250000);
    }

    public function test_index_is_paginated_and_filterable_by_status(): void
    {
        Quote::factory()->count(3)->create();
        Quote::factory()->count(2)->sent()->create();

        $this->getJson('/api/v1/quotes')->assertOk()->assertJsonCount(5, 'data')->assertJsonPath('meta.total', 5);
        $this->getJson('/api/v1/quotes?status=sent')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/quotes?per_page=2')->assertJsonCount(2, 'data')->assertJsonPath('meta.last_page', 3);
    }

    public function test_page_links_keep_the_filters(): void
    {
        Quote::factory()->count(3)->sent()->create();
        Quote::factory()->create();

        $response = $this->getJson('/api/v1/quotes?status=sent&per_page=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 3);

        $next = $response->json('links.next');
        $this->assertStringContainsString('status=sent', $next);
        $this->assertStringContainsString('per_page=1', $next);
    }

    public function test_list_filters_are_validated(): void
    {
        $quote = Quote::factory()->create();
        Quote::factory()->create();

        $this->getJson("/api/v1/quotes?customer_id={$quote->customer_id}")->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/quotes?status=shipped')->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->getJson('/api/v1/quotes?customer_id[]=1')->assertUnprocessable()->assertJsonValidationErrors('customer_id');
    }

    public function test_show_update_and_delete_a_draft(): void
    {
        $quote = Quote::factory()->withItems()->create();

        $this->getJson("/api/v1/quotes/{$quote->id}")->assertOk()->assertJsonCount(3, 'data.items');

        $this->putJson("/api/v1/quotes/{$quote->id}", $this->payload($quote->customer))
            ->assertOk()->assertJsonCount(1, 'data.items');

        $this->deleteJson("/api/v1/quotes/{$quote->id}")->assertNoContent();
    }

    public function test_sent_quotes_are_read_only(): void
    {
        $quote = Quote::factory()->sent()->create();

        $this->putJson("/api/v1/quotes/{$quote->id}", $this->payload($quote->customer))
            ->assertUnprocessable()->assertJsonPath('message', 'Only draft quotes can be changed or deleted.');
        $this->deleteJson("/api/v1/quotes/{$quote->id}")->assertUnprocessable();
    }

    public function test_status_workflow_and_conversion(): void
    {
        $quote = Quote::factory()->withItems(2)->create();

        $this->postJson("/api/v1/quotes/{$quote->id}/status", ['status' => 'sent'])->assertOk()->assertJsonPath('data.status', 'sent');
        $this->postJson("/api/v1/quotes/{$quote->id}/convert")->assertUnprocessable();
        $this->postJson("/api/v1/quotes/{$quote->id}/status", ['status' => 'accepted'])->assertOk();

        $this->postJson("/api/v1/quotes/{$quote->id}/convert")
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.quote_id', $quote->id)
            ->assertJsonPath('data.total_cents', $quote->fresh()->total_cents)
            ->assertJsonCount(2, 'data.items');

        $this->postJson("/api/v1/quotes/{$quote->id}/convert")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This quote has already been converted to an order.');
    }

    public function test_illegal_and_unknown_status_values(): void
    {
        $quote = Quote::factory()->create();

        $this->postJson("/api/v1/quotes/{$quote->id}/status", ['status' => 'accepted'])
            ->assertUnprocessable()->assertJsonPath('message', 'A draft quote cannot become accepted.');
        $this->postJson("/api/v1/quotes/{$quote->id}/status", ['status' => 'nonsense'])
            ->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    public function test_domain_errors_are_json_even_without_an_accept_header(): void
    {
        $quote = Quote::factory()->create();

        $this->post("/api/v1/quotes/{$quote->id}/convert")->assertUnprocessable()->assertJsonStructure(['message']);
    }
}
