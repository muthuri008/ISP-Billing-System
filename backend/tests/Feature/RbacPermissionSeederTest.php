<?php

namespace Tests\Feature;

use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacPermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_route_permission_slugs_are_seeded_for_the_super_admin(): void
    {
        $this->seed(RbacSeeder::class);

        foreach (['network.manage', 'subscriptions.manage', 'billing.manage'] as $slug) {
            $this->assertDatabaseHas('permissions', ['slug' => $slug]);
        }

        $this->assertDatabaseHas('roles', ['slug' => 'super-admin']);
    }
}
