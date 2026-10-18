<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_credentials_return_a_bearer_token_that_works(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);

        $token = $this->postJson('/api/v1/auth/token', ['email' => $user->email, 'password' => 'secret-pass', 'device_name' => 'curl'])
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->json('token');

        $this->withToken($token)->getJson('/api/v1/customers')->assertOk();
    }

    public function test_invalid_credentials_return_401(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/token', ['email' => $user->email, 'password' => 'wrong'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid credentials.');
    }

    public function test_token_request_is_validated(): void
    {
        $this->postJson('/api/v1/auth/token', [])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_non_string_email_is_a_validation_error(): void
    {
        $this->postJson('/api/v1/auth/token', ['email' => ['a@example.com'], 'password' => 'x'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_requests_without_a_token_get_401_json(): void
    {
        $this->getJson('/api/v1/quotes')->assertUnauthorized();
        $this->get('/api/v1/quotes')->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_token_can_be_revoked(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);
        $token = $this->postJson('/api/v1/auth/token', ['email' => $user->email, 'password' => 'secret-pass'])->json('token');

        $this->withToken($token)->deleteJson('/api/v1/auth/token')->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_token_endpoint_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/token', ['email' => 'a@example.com', 'password' => 'x']);
        }

        $this->postJson('/api/v1/auth/token', ['email' => 'a@example.com', 'password' => 'x'])->assertStatus(429);
    }

    public function test_api_is_rate_limited_per_user(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');

        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v1/customers')->assertOk();
        }

        $this->getJson('/api/v1/customers')->assertStatus(429);
    }
}
