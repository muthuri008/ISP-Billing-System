<?php

namespace App\Services\Subscription;

use App\Models\Customer;
use App\Models\Package;
use App\Models\Subscription;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    public function create(Customer $customer, Package $package, array $data): Subscription
    {
        if ($customer->status === 'disconnected') {
            throw ValidationException::withMessages(['customer_id' => ['Disconnected customers cannot receive subscriptions.']]);
        }

        if (! $package->is_active) {
            throw ValidationException::withMessages(['package_id' => ['The selected package is inactive.']]);
        }

        $startsAt = now()->parse($data['starts_at']);
        $cycleDays = match ($package->billing_cycle) {
            'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            'quarterly' => 90,
            'annual' => 365,
        };

        return Subscription::create([
            'subscription_number' => 'SUB-'.now()->format('Ym').'-'.strtoupper(Str::random(6)),
            'customer_id' => $customer->id,
            'package_id' => $package->id,
            'status' => 'active',
            'starts_at' => $startsAt->toDateString(),
            'next_billing_at' => $startsAt->copy()->addDays($cycleDays)->toDateString(),
            'auto_renew' => $data['auto_renew'] ?? true,
            'recurring_price' => $package->price,
            'currency' => $package->currency,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function suspend(Subscription $subscription): Subscription
    {
        $subscription->update(['status' => 'suspended']);
        return $subscription->fresh();
    }

    public function activate(Subscription $subscription): Subscription
    {
        if ($subscription->status === 'cancelled') {
            throw ValidationException::withMessages(['status' => ['Cancelled subscriptions cannot be activated.']]);
        }
        $subscription->update(['status' => 'active']);
        return $subscription->fresh();
    }

    public function cancel(Subscription $subscription): Subscription
    {
        $subscription->update(['status' => 'cancelled', 'auto_renew' => false, 'ends_at' => now()->toDateString()]);
        return $subscription->fresh();
    }
}
