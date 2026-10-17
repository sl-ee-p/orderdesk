<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Quote;
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
}
