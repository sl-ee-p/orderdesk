<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Quote;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_consistent_demo_data(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'demo@orderdesk.test']);
        $this->assertGreaterThan(0, Order::count());

        foreach (Quote::with('items')->get() as $quote) {
            $this->assertSame($quote->items->sum('line_total_cents'), $quote->subtotal_cents);
            $this->assertSame($quote->subtotal_cents + $quote->tax_cents, $quote->total_cents);
        }
    }

    public function test_seeding_twice_does_not_duplicate_data(): void
    {
        $this->seed(DemoSeeder::class);
        $quotes = Quote::count();

        $this->seed(DemoSeeder::class);

        $this->assertSame($quotes, Quote::count());
        $this->assertSame(1, User::where('email', 'demo@orderdesk.test')->count());
    }

    public function test_every_page_renders_with_demo_data(): void
    {
        $this->seed(DemoSeeder::class);
        $this->actingAs(User::firstOrFail());

        foreach (['/quotes', '/orders', '/customers', '/products'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get('/customers/'.Customer::firstOrFail()->id)->assertOk();
        $this->get('/quotes/'.Quote::firstOrFail()->id)->assertOk();
        $this->get('/orders/'.Order::firstOrFail()->id)->assertOk();
    }
}
