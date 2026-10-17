<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_index_is_paginated(): void
    {
        Customer::factory()->count(30)->create();

        $this->getJson('/api/v1/customers')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.total', 30)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonStructure(['data' => [['id', 'name', 'email']], 'links', 'meta']);

        $this->getJson('/api/v1/customers?per_page=10&page=3')->assertJsonCount(10, 'data');
    }

    public function test_per_page_is_capped(): void
    {
        Customer::factory()->count(3)->create();

        $this->getJson('/api/v1/customers?per_page=100000')->assertOk()->assertJsonPath('meta.per_page', 100);
        $this->getJson('/api/v1/customers?per_page=0')->assertOk()->assertJsonPath('meta.per_page', 1);
    }

    public function test_crud_cycle(): void
    {
        $id = $this->postJson('/api/v1/customers', ['name' => 'Jane', 'email' => 'jane@example.com'])
            ->assertCreated()
            ->assertJsonPath('data.email', 'jane@example.com')
            ->json('data.id');

        $this->getJson("/api/v1/customers/{$id}")->assertOk()->assertJsonPath('data.name', 'Jane');

        $this->putJson("/api/v1/customers/{$id}", ['name' => 'Jane Doe', 'email' => 'jane@example.com'])
            ->assertOk()->assertJsonPath('data.name', 'Jane Doe');

        $this->deleteJson("/api/v1/customers/{$id}")->assertNoContent();
        $this->getJson("/api/v1/customers/{$id}")->assertNotFound();
    }

    public function test_validation_errors_are_422_json(): void
    {
        Customer::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/v1/customers', ['email' => 'taken@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_customer_with_documents_cannot_be_deleted(): void
    {
        $quote = Quote::factory()->create();

        $this->deleteJson("/api/v1/customers/{$quote->customer_id}")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This customer has quotes or orders and cannot be deleted.');
    }
}
