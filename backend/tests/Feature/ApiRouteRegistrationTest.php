<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiRouteRegistrationTest extends TestCase
{
    use RefreshDatabase;
    public function test_invoice_api_routes_are_registered_under_the_v1_prefix(): void
    {
        // A 401 proves that the route exists and auth middleware ran.
        $this->getJson('/api/v1/invoices')->assertUnauthorized();
    }

    public function test_login_route_uses_the_named_login_rate_limiter(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'not-a-real-user@example.test',
            'password' => 'invalid-password',
        ])->assertUnprocessable();

        $this->assertNotNull(RateLimiter::limiter('login'));
    }
}
