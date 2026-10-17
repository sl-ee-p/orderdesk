<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use App\Services\OrderService;
use App\Services\QuoteConversionService;
use Illuminate\Database\Seeder;

/**
 * Fake demo data only. Log in with demo@orderdesk.test / password.
 */
class DemoSeeder extends Seeder
{
    /** @var array<string, array{string, int}> sku => [name, unit price in cents] */
    private const PRODUCTS = [
        'KB-1001' => ['Mechanical keyboard', 8990],
        'MS-1002' => ['Wireless mouse', 3490],
        'MN-2001' => ['27" 4K monitor', 38900],
        'MN-2002' => ['24" office monitor', 15900],
        'DK-3001' => ['USB-C docking station', 12900],
        'CB-4001' => ['USB-C cable 2 m', 990],
        'HS-5001' => ['Noise-cancelling headset', 15900],
        'WC-5002' => ['HD webcam', 6900],
        'LP-6001' => ['Business laptop 14"', 112900],
        'LP-6002' => ['Business laptop 16"', 139900],
        'ST-7001' => ['Laptop stand', 4500],
        'CH-8001' => ['Ergonomic office chair', 24900],
        'DS-8002' => ['Height-adjustable desk', 42900],
        'PR-9001' => ['Laser printer', 21900],
        'TN-9002' => ['Toner cartridge (black)', 7900],
    ];

    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo Admin',
            'email' => 'demo@orderdesk.test',
        ]);

        foreach (self::PRODUCTS as $sku => [$name, $price]) {
            Product::factory()->create(['sku' => $sku, 'name' => $name, 'unit_price_cents' => $price]);
        }
        Product::factory()->inactive()->create(['sku' => 'OLD-0001', 'name' => 'Discontinued VGA adapter', 'unit_price_cents' => 1200]);

        $customers = Customer::factory()->count(12)->create();

        foreach ($customers as $customer) {
            Quote::factory()->for($customer)->for($user)->withItems(fake()->numberBetween(1, 5))->create();
            Quote::factory()->for($customer)->for($user)->sent()->withItems(fake()->numberBetween(2, 4))->create();
            Quote::factory()->for($customer)->for($user)->rejected()->withItems(2)->create();
        }

        // Accepted quotes, each converted to an order and moved along the workflow.
        $conversion = app(QuoteConversionService::class);
        $orders = app(OrderService::class);
        $paths = [
            [],
            [OrderStatus::Confirmed],
            [OrderStatus::Confirmed, OrderStatus::Shipped],
            [OrderStatus::Cancelled],
        ];

        foreach ($customers->random(8) as $index => $customer) {
            $quote = Quote::factory()->for($customer)->for($user)->accepted()->withItems(fake()->numberBetween(1, 4))->create();
            $order = $conversion->convert($quote);

            foreach ($paths[$index % count($paths)] as $status) {
                $orders->transition($order, $status);
            }
        }
    }
}
