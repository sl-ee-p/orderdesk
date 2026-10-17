<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_pages_render(): void
    {
        $order = Order::factory()->withItems()->create();

        $this->get('/orders')->assertOk()->assertSee($order->number);
        $this->get("/orders/{$order->id}")->assertOk()->assertSee('Confirm');
    }

    public function test_order_goes_from_draft_to_confirmed_to_shipped_with_timestamps(): void
    {
        $order = Order::factory()->create();

        $this->post("/orders/{$order->id}/status", ['status' => 'confirmed']);
        $order->refresh();
        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertNotNull($order->confirmed_at);
        $this->assertNull($order->shipped_at);

        $this->post("/orders/{$order->id}/status", ['status' => 'shipped']);
        $order->refresh();
        $this->assertSame(OrderStatus::Shipped, $order->status);
        $this->assertNotNull($order->shipped_at);
    }

    public function test_draft_and_confirmed_orders_can_be_cancelled(): void
    {
        foreach (['draft', 'confirmed'] as $state) {
            $order = Order::factory()->{$state}()->create();

            $this->post("/orders/{$order->id}/status", ['status' => 'cancelled']);

            $order->refresh();
            $this->assertSame(OrderStatus::Cancelled, $order->status);
            $this->assertNotNull($order->cancelled_at);
        }
    }

    public function test_illegal_transitions_are_refused(): void
    {
        $draft = Order::factory()->create();
        $shipped = Order::factory()->shipped()->create();
        $cancelled = Order::factory()->cancelled()->create();

        $this->post("/orders/{$draft->id}/status", ['status' => 'shipped'])
            ->assertSessionHas('error', 'A draft order cannot become shipped.');
        $this->post("/orders/{$shipped->id}/status", ['status' => 'cancelled'])->assertSessionHas('error');
        $this->post("/orders/{$cancelled->id}/status", ['status' => 'confirmed'])->assertSessionHas('error');

        $this->assertSame(OrderStatus::Draft, $draft->fresh()->status);
        $this->assertSame(OrderStatus::Shipped, $shipped->fresh()->status);
        $this->assertSame(OrderStatus::Cancelled, $cancelled->fresh()->status);
    }
}
