<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceGenerationService
{
    public function generateForSubscription(Subscription $subscription, ?Carbon $billingDate = null): ?Invoice
    {
        $billingDate ??= now()->startOfDay();

        return DB::transaction(function () use ($subscription, $billingDate) {
            if ($subscription->status !== 'active' || ! $subscription->auto_renew) return null;
            if ($subscription->starts_at && $subscription->starts_at->startOfDay()->gt($billingDate)) return null;
            if ($subscription->ends_at && $subscription->ends_at->startOfDay()->lt($billingDate)) return null;
            if ($subscription->next_billing_at && $subscription->next_billing_at->startOfDay()->gt($billingDate)) return null;

            $periodStart = $subscription->next_billing_at?->startOfDay() ?? $billingDate->copy();
            $periodEnd = match ($subscription->package->billing_cycle) {
                'weekly' => $periodStart->copy()->addWeek()->subDay(),
                'quarterly' => $periodStart->copy()->addMonths(3)->subDay(),
                'annual', 'yearly' => $periodStart->copy()->addYear()->subDay(),
                default => $periodStart->copy()->addMonth()->subDay(),
            };

            $existing = Invoice::where('subscription_id', $subscription->id)
                ->whereDate('period_start', $periodStart)
                ->whereDate('period_end', $periodEnd)
                ->first();
            if ($existing) return $existing;

            $amount = (float) $subscription->recurring_price;
            $invoice = Invoice::create([
                'invoice_number' => 'INV-' . now()->format('Ym') . '-' . strtoupper(bin2hex(random_bytes(4))),
                'customer_id' => $subscription->customer_id,
                'subscription_id' => $subscription->id,
                'invoice_date' => $billingDate,
                'due_date' => $billingDate->copy()->addDays(7),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'status' => 'issued',
                'subtotal' => $amount,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => $amount,
                'amount_paid' => 0,
                'amount_due' => $amount,
                'currency' => $subscription->currency ?: $subscription->package->currency,
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $subscription->package->name . ' subscription (' . $periodStart->toDateString() . ' to ' . $periodEnd->toDateString() . ')',
                'quantity' => 1,
                'unit_price' => $amount,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => $amount,
            ]);

            $subscription->update(['next_billing_at' => $periodEnd->copy()->addDay()]);
            return $invoice->fresh(['items']);
        });
    }
}
