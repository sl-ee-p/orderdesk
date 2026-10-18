<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_index_lists_customers(): void
    {
        $customer = Customer::factory()->create(['name' => 'Acme Ltd']);

        $this->get('/customers')->assertOk()->assertSee('Acme Ltd');
        $this->get("/customers/{$customer->id}")->assertOk()->assertSee($customer->email);
    }

    public function test_customer_can_be_created_and_updated(): void
    {
        $this->post('/customers', ['name' => 'Jane', 'email' => 'jane@example.com'])->assertRedirect();
        $customer = Customer::where('email', 'jane@example.com')->firstOrFail();

        $this->put("/customers/{$customer->id}", ['name' => 'Jane Doe', 'email' => 'jane@example.com'])
            ->assertRedirect("/customers/{$customer->id}");

        $this->assertSame('Jane Doe', $customer->fresh()->name);
    }

    public function test_validation_rejects_duplicate_email_and_missing_name(): void
    {
        Customer::factory()->create(['email' => 'taken@example.com']);

        $this->post('/customers', ['name' => '', 'email' => 'taken@example.com'])
            ->assertSessionHasErrors(['name', 'email']);
    }

    public function test_customer_without_documents_can_be_deleted(): void
    {
        $customer = Customer::factory()->create();

        $this->delete("/customers/{$customer->id}")->assertRedirect('/customers');
        $this->assertModelMissing($customer);
    }

    public function test_customer_with_quotes_cannot_be_deleted(): void
    {
        $quote = Quote::factory()->create();

        $this->delete("/customers/{$quote->customer_id}")->assertSessionHas('error');
        $this->assertModelExists($quote->customer);
    }

    public function test_document_created_during_deletion_still_blocks_it(): void
    {
        $customer = Customer::factory()->create();
        // Simulate another request adding a quote between the "has documents?" check and the delete.
        Customer::deleting(fn (Customer $deleting) => Quote::factory()->for($deleting)->create());

        try {
            $this->delete("/customers/{$customer->id}")->assertSessionHas('error');
        } finally {
            Customer::flushEventListeners();
        }

        $this->assertModelExists($customer);
    }
}
