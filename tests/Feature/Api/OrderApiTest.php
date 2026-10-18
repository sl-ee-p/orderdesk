<?php

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_index_show_and_filter(): void
    {
        $order = Order::factory()->withItems(2)->create();
        Order::factory()->count(2)->shipped()->create();

        $this->getJson('/api/v1/orders')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/api/v1/orders?status=shipped')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson("/api/v1/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.number', $order->number)
            ->assertJsonCount(2, 'data.items');
    }

    public function test_list_filters_are_validated(): void
    {
        $order = Order::factory()->create();
        Order::factory()->create();

        $this->getJson("/api/v1/orders?customer_id={$order->customer_id}")->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/orders?status=')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/orders?status=sent')->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->getJson('/api/v1/orders?status[]=draft')->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->getJson('/api/v1/orders?customer_id=abc')->assertUnprocessable()->assertJsonValidationErrors('customer_id');
    }

    public function test_orders_cannot_be_created_or_deleted_directly(): void
    {
        $order = Order::factory()->create();

        $this->postJson('/api/v1/orders', [])->assertStatus(405);
        $this->deleteJson("/api/v1/orders/{$order->id}")->assertStatus(405);
    }

    public function test_status_workflow(): void
    {
        $order = Order::factory()->create();

        $this->postJson("/api/v1/orders/{$order->id}/status", ['status' => 'confirmed'])
            ->assertOk()->assertJsonPath('data.status', 'confirmed');
        $this->assertNotNull($order->fresh()->confirmed_at);

        $this->postJson("/api/v1/orders/{$order->id}/status", ['status' => 'shipped'])->assertOk();
        $this->postJson("/api/v1/orders/{$order->id}/status", ['status' => 'cancelled'])
            ->assertUnprocessable()->assertJsonPath('message', 'A shipped order cannot become cancelled.');

        $this->assertSame(OrderStatus::Shipped, $order->fresh()->status);
    }

    public function test_unknown_order_returns_404_json(): void
    {
        $this->getJson('/api/v1/orders/999')->assertNotFound();
    }
}
