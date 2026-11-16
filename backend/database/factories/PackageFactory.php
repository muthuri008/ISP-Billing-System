<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<\App\Models\Package> */
class PackageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'PKG-'.Str::upper(Str::random(8)),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'download_mbps' => fake()->numberBetween(5, 100),
            'upload_mbps' => fake()->numberBetween(2, 50),
            'price' => fake()->numberBetween(500, 10000),
            'currency' => 'KES',
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ];
    }
}
