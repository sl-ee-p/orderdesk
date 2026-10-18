<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_price_is_stored_as_cents(): void
    {
        $this->post('/products', ['sku' => 'AB-1', 'name' => 'Widget', 'unit_price' => '12.50', 'is_active' => '1'])
            ->assertRedirect('/products');

        $this->assertDatabaseHas('products', ['sku' => 'AB-1', 'unit_price_cents' => 1250, 'is_active' => true]);
    }

    public function test_invalid_price_and_duplicate_sku_are_rejected(): void
    {
        Product::factory()->create(['sku' => 'AB-1']);

        $this->post('/products', ['sku' => 'AB-1', 'name' => 'Widget', 'unit_price' => '12.345'])
            ->assertSessionHasErrors(['sku', 'unit_price']);
    }

    public function test_malformed_and_out_of_range_prices_are_validation_errors(): void
    {
        $this->post('/products', ['sku' => 'AB-1', 'name' => 'Widget', 'unit_price' => ['12']])
            ->assertSessionHasErrors('unit_price');
        $this->post('/products', ['sku' => 'AB-1', 'name' => 'Widget', 'unit_price' => '12,50'])
            ->assertSessionHasErrors('unit_price');
        $this->post('/products', ['sku' => 'AB-1', 'name' => 'Widget', 'unit_price' => '42949672.96'])
            ->assertSessionHasErrors('unit_price');
        $this->post('/products', ['sku' => 'AB-1', 'name' => 'Widget', 'unit_price' => '99999999999999999999'])
            ->assertSessionHasErrors('unit_price');

        $this->assertDatabaseCount('products', 0);

        $this->post('/products', ['sku' => 'AB-1', 'name' => 'Widget', 'unit_price' => '42,949,672.95'])
            ->assertRedirect('/products');
        $this->assertDatabaseHas('products', ['sku' => 'AB-1', 'unit_price_cents' => 4294967295]);
    }

    public function test_product_can_be_deactivated_and_deleted(): void
    {
        $product = Product::factory()->create(['unit_price_cents' => 500]);

        $this->put("/products/{$product->id}", [
            'sku' => $product->sku, 'name' => $product->name, 'unit_price' => '5.00', 'is_active' => '0',
        ])->assertRedirect('/products');
        $this->assertFalse($product->fresh()->is_active);

        $this->delete("/products/{$product->id}")->assertRedirect('/products');
        $this->assertModelMissing($product);
    }

    public function test_index_and_forms_render(): void
    {
        $product = Product::factory()->create();

        $this->get('/products')->assertOk()->assertSee($product->sku);
        $this->get('/products/create')->assertOk();
        $this->get("/products/{$product->id}/edit")->assertOk();
    }
}
