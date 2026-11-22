<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Subscription;
use App\Services\Billing\InvoiceGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('generates one invoice for an active subscription and advances billing', function () {
    $customer = Customer::factory()->create();
    $package = Package::factory()->create(['billing_cycle' => 'monthly', 'price' => 2500, 'currency' => 'KES']);
    $subscription = Subscription::factory()->create([
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'status' => 'active',
        'starts_at' => '2026-08-16',
        'auto_renew' => true,
        'recurring_price' => 2500,
        'currency' => 'KES',
        'next_billing_at' => '2026-08-16',
    ]);

    $invoice = app(InvoiceGenerationService::class)->generateForSubscription($subscription, Carbon::parse('2026-08-16'));

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and((float) $invoice->total_amount)->toBe(2500.0)
        ->and((float) $invoice->amount_due)->toBe(2500.0)
        ->and($invoice->items)->toHaveCount(1);

    $subscription->refresh();
    expect($subscription->next_billing_at->toDateString())->toBe('2026-09-16');
});

it('does not create duplicate invoices for the same billing period', function () {
    $customer = Customer::factory()->create();
    $package = Package::factory()->create(['billing_cycle' => 'monthly', 'price' => 2500]);
    $subscription = Subscription::factory()->create([
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'status' => 'active',
        'starts_at' => '2026-08-16',
        'auto_renew' => true,
        'recurring_price' => 2500,
        'next_billing_at' => '2026-08-16',
    ]);

    $service = app(InvoiceGenerationService::class);
    $first = $service->generateForSubscription($subscription, Carbon::parse('2026-08-16'));
    $subscription->refresh()->update(['next_billing_at' => '2026-08-16']);
    $second = $service->generateForSubscription($subscription->refresh(), Carbon::parse('2026-08-16'));

    expect($second->id)->toBe($first->id);
    expect(Invoice::where('subscription_id', $subscription->id)->count())->toBe(1);
    expect($subscription->refresh()->next_billing_at->toDateString())->toBe('2026-09-16');
});


it('creates a manual invoice with the subscription currency and advances the billing cursor', function () {
    $customer = Customer::factory()->create();
    $package = Package::factory()->create([
        'billing_cycle' => 'weekly',
        'price' => 900,
        'currency' => 'USD',
    ]);
    $subscription = Subscription::factory()->create([
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'status' => 'active',
        'starts_at' => '2026-08-16',
        'auto_renew' => true,
        'recurring_price' => 900,
        'currency' => 'USD',
        'next_billing_at' => '2026-08-16',
    ]);

    $invoice = app(InvoiceService::class)->createForSubscription($subscription);

    expect($invoice->currency)->toBe('USD')
        ->and((float) $invoice->total_amount)->toBe(900.0)
        ->and($invoice->items)->toHaveCount(1)
        ->and($subscription->refresh()->next_billing_at->toDateString())->toBe('2026-08-23');
});
