<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<\App\Models\Subscription> */
class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        $package = Package::factory();

        return [
            'subscription_number' => 'SUB-'.Str::upper(Str::random(10)),
            'customer_id' => Customer::factory(),
            'package_id' => $package,
            'status' => 'pending',
            'starts_at' => now()->toDateString(),
            'next_billing_at' => now()->toDateString(),
            'auto_renew' => true,
            'recurring_price' => 2500,
            'currency' => 'KES',
        ];
    }
}
