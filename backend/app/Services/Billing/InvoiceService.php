<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function createForSubscription(Subscription $subscription, ?string $dueDate = null): Invoice
    {
        if (! in_array($subscription->status, ['active', 'suspended'], true)) {
            throw ValidationException::withMessages(['subscription' => ['Only active or suspended subscriptions can be invoiced.']]);
        }

        $periodStart = $subscription->next_billing_at ?? $subscription->starts_at;
        $periodEnd = match ($subscription->package->billing_cycle) {
            'daily' => $periodStart->copy()->addDay()->subDay(),
            'weekly' => $periodStart->copy()->addWeek()->subDay(),
            'monthly' => $periodStart->copy()->addMonth()->subDay(),
            'quarterly' => $periodStart->copy()->addMonths(3)->subDay(),
            'annual' => $periodStart->copy()->addYear()->subDay(),
        };

        $amount = (float) $subscription->recurring_price;
        return Invoice::create([
            'invoice_number' => 'INV-'.now()->format('Ym').'-'.strtoupper(Str::random(7)),
            'customer_id' => $subscription->customer_id,
            'subscription_id' => $subscription->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => $dueDate ?? now()->addDays(7)->toDateString(),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => 'issued',
            'subtotal' => $amount,
            'total_amount' => $amount,
            'amount_due' => $amount,
            'currency' => $subscription->currency,
        ])->tap(function (Invoice $invoice) use ($amount) {
            $invoice->items()->create([
                'description' => $invoice->subscription->package->name.' subscription',
                'quantity' => 1,
                'unit_price' => $amount,
                'line_total' => $amount,
            ]);
        });
    }
}
