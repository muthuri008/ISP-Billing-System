<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<\App\Models\Customer> */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_number' => 'CUS-'.Str::upper(Str::random(10)),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '2547'.fake()->numerify('########'),
            'status' => 'active',
            'billing_type' => 'postpaid',
            'registered_at' => now()->toDateString(),
        ];
    }
}
