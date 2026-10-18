<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_price_is_exchanged_as_integer_cents(): void
    {
        $this->postJson('/api/v1/products', ['sku' => 'AB-1', 'name' => 'Widget', 'unit_price_cents' => 1250, 'is_active' => true])
            ->assertCreated()
            ->assertJsonPath('data.unit_price_cents', 1250)
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('products', ['sku' => 'AB-1', 'unit_price_cents' => 1250]);
    }

    public function test_validation_rejects_bad_price_and_duplicate_sku(): void
    {
        Product::factory()->create(['sku' => 'AB-1']);

        $this->postJson('/api/v1/products', ['sku' => 'AB-1', 'name' => 'W', 'unit_price_cents' => -5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sku', 'unit_price_cents']);
        $this->postJson('/api/v1/products', ['sku' => 'ZZ-1', 'name' => 'W', 'unit_price_cents' => '12.50'])
            ->assertJsonValidationErrors('unit_price_cents');
    }

    public function test_omitted_is_active_defaults_to_active_and_is_kept_on_update(): void
    {
        $this->postJson('/api/v1/products', ['sku' => 'AB-1', 'name' => 'Widget', 'unit_price_cents' => 100])
            ->assertCreated()
            ->assertJsonPath('data.is_active', true);

        $inactive = Product::factory()->inactive()->create();
        $this->putJson("/api/v1/products/{$inactive->id}", ['sku' => $inactive->sku, 'name' => 'Renamed', 'unit_price_cents' => 100])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $active = Product::factory()->create(['is_active' => true]);
        $this->putJson("/api/v1/products/{$active->id}", ['sku' => $active->sku, 'name' => 'Renamed', 'unit_price_cents' => 100])
            ->assertOk()
            ->assertJsonPath('data.is_active', true);
    }

    public function test_update_list_and_delete(): void
    {
        $product = Product::factory()->create();

        $this->putJson("/api/v1/products/{$product->id}", [
            'sku' => $product->sku, 'name' => 'Renamed', 'unit_price_cents' => 999, 'is_active' => false,
        ])->assertOk()->assertJsonPath('data.name', 'Renamed')->assertJsonPath('data.is_active', false);

        $this->getJson('/api/v1/products')->assertOk()->assertJsonCount(1, 'data');
        $this->deleteJson("/api/v1/products/{$product->id}")->assertNoContent();
    }
}
