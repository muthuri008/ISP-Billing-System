<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'module' => 'dashboard'],
            ['name' => 'Manage Customers', 'slug' => 'customers.manage', 'module' => 'customers'],
            ['name' => 'Manage Packages', 'slug' => 'packages.manage', 'module' => 'packages'],
            ['name' => 'Manage Routers', 'slug' => 'routers.manage', 'module' => 'network'],
            ['name' => 'Manage Billing', 'slug' => 'billing.manage', 'module' => 'billing'],
            ['name' => 'Manage Payments', 'slug' => 'payments.manage', 'module' => 'payments'],
            ['name' => 'View Reports', 'slug' => 'reports.view', 'module' => 'reports'],
            ['name' => 'Manage Users', 'slug' => 'users.manage', 'module' => 'administration'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['slug' => $permission['slug']], $permission);
        }

        $admin = Role::updateOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Administrator', 'description' => 'Full system administration access', 'is_system' => true]
        );

        $admin->permissions()->sync(Permission::query()->pluck('id'));

        Role::updateOrCreate(
            ['slug' => 'support'],
            ['name' => 'Support Agent', 'description' => 'Customer support and operational access', 'is_system' => true]
        );
    }
}
