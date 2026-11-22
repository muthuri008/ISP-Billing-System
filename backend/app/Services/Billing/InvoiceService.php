<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function createForSubscription(Subscription $subscription, ?string $dueDate = null): Invoice
    {
        return DB::transaction(function () use ($subscription, $dueDate) {
            $subscription = Subscription::query()
                ->with('package')
                ->lockForUpdate()
                ->findOrFail($subscription->id);

            if (! in_array($subscription->status, ['active', 'suspended'], true)) {
                throw ValidationException::withMessages([
                    'subscription' => ['Only active or suspended subscriptions can be invoiced.'],
                ]);
            }

            $periodStart = ($subscription->next_billing_at ?? $subscription->starts_at ?? now())->copy()->startOfDay();
            $periodEnd = match ($subscription->package->billing_cycle) {
                'daily' => $periodStart->copy(),
                'weekly' => $periodStart->copy()->addWeek()->subDay(),
                'quarterly' => $periodStart->copy()->addMonths(3)->subDay(),
                'annual', 'yearly' => $periodStart->copy()->addYear()->subDay(),
                default => $periodStart->copy()->addMonth()->subDay(),
            };

            $existing = Invoice::query()
                ->where('subscription_id', $subscription->id)
                ->whereDate('period_start', $periodStart)
                ->whereDate('period_end', $periodEnd)
                ->first();

            if ($existing) {
                if (! $subscription->next_billing_at || $subscription->next_billing_at->startOfDay()->equalTo($periodStart)) {
                    $subscription->update(['next_billing_at' => $periodEnd->copy()->addDay()]);
                }

                return $existing;
            }

            $amount = (float) ($subscription->recurring_price ?? $subscription->package->price);
            $currency = strtoupper((string) ($subscription->currency ?: $subscription->package->currency ?: 'KES'));

            $invoice = Invoice::create([
                'invoice_number' => 'INV-'.now()->format('Ym').'-'.strtoupper(Str::random(7)),
                'customer_id' => $subscription->customer_id,
                'subscription_id' => $subscription->id,
                'invoice_date' => now()->toDateString(),
                'due_date' => $dueDate ?? now()->addDays(7)->toDateString(),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'status' => 'issued',
                'subtotal' => $amount,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => $amount,
                'amount_paid' => 0,
                'amount_due' => $amount,
                'currency' => $currency,
            ]);

            $invoice->items()->create([
                'description' => $subscription->package->name.' subscription ('.$periodStart->toDateString().' to '.$periodEnd->toDateString().')',
                'quantity' => 1,
                'unit_price' => $amount,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'line_total' => $amount,
            ]);

            $subscription->update(['next_billing_at' => $periodEnd->copy()->addDay()]);

            return $invoice->fresh(['customer', 'subscription.package', 'items']);
        });
    }
}
