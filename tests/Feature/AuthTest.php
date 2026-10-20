<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/quotes')->assertRedirect('/login');
        $this->get('/customers')->assertRedirect('/login');
    }

    public function test_demo_login_hint_only_appears_when_the_demo_account_exists(): void
    {
        $this->get('/login')->assertOk()->assertDontSee('Demo login');

        User::factory()->create(['email' => 'demo@orderdesk.test']);

        $this->get('/login')->assertOk()->assertSee('Demo login: demo@orderdesk.test / password');
    }

    public function test_user_can_log_in_and_out(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass'])
            ->assertRedirect('/quotes');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'nope'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'a@example.com', 'password' => 'x']);
        }

        $this->post('/login', ['email' => 'a@example.com', 'password' => 'x'])->assertStatus(429);
    }

    public function test_rate_limit_ignores_email_case_and_whitespace(): void
    {
        foreach (['a@example.com', 'A@Example.com', ' a@example.com', 'A@EXAMPLE.COM', 'a@EXAMPLE.com'] as $email) {
            $this->post('/login', ['email' => $email, 'password' => 'x']);
        }

        $this->post('/login', ['email' => 'a@example.COM', 'password' => 'x'])->assertStatus(429);
    }

    public function test_non_string_email_is_a_validation_error(): void
    {
        $this->from('/login')->post('/login', ['email' => ['a@example.com'], 'password' => 'x'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
    }
}
