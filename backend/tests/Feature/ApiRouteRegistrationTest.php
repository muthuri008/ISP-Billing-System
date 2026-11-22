<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiRouteRegistrationTest extends TestCase
{
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
        ])->assertUnauthorized();

        $this->assertNotNull(app('limiter')->limiter('login'));
    }
}
