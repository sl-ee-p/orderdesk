<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Safety net: a new route added without authentication fails this test.
 */
class RouteProtectionTest extends TestCase
{
    use RefreshDatabase;

    /** Intentionally public routes, as "METHOD uri". */
    private const PUBLIC_ROUTES = [
        'GET login',
        'POST login',
        'POST api/v1/auth/token',
        'GET up',
        'GET sanctum/csrf-cookie',
    ];

    /**
     * @return list<LaravelRoute>
     */
    private function appRoutes(): array
    {
        return array_values(array_filter(
            Route::getRoutes()->getRoutes(),
            fn (LaravelRoute $route) => ! str_starts_with($route->uri(), 'storage/')
                && ! in_array($route->methods()[0].' '.$route->uri(), self::PUBLIC_ROUTES, true),
        ));
    }

    public function test_every_application_route_requires_authentication(): void
    {
        $routes = $this->appRoutes();
        $this->assertNotEmpty($routes);

        foreach ($routes as $route) {
            $middleware = $route->gatherMiddleware();
            $isProtected = in_array('auth', $middleware, true) || in_array('auth:sanctum', $middleware, true);

            $this->assertTrue($isProtected, "Route [{$route->methods()[0]} {$route->uri()}] is not protected.");
        }
    }

    public function test_api_routes_answer_401_without_a_token(): void
    {
        $this->getJson('/api/v1/customers/1')->assertUnauthorized();
        $this->postJson('/api/v1/quotes/1/convert')->assertUnauthorized();
        $this->postJson('/api/v1/orders/1/status', ['status' => 'confirmed'])->assertUnauthorized();
    }
}
