<?php
namespace Tests\Feature;
use Tests\TestCase;
class StellarTechnologiesSmokeTest extends TestCase
{
    public function test_health_endpoint_is_available(): void
    {
        $this->getJson('/api/v1/health')->assertSuccessful();
    }

    public function test_unauthenticated_api_requests_are_rejected(): void
    {
        $this->getJson('/api/v1/customers')->assertUnauthorized();
    }
}
