<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function record(array $data): Payment
    {
        $payment = Payment::create([
            ...$data,
            'payment_number' => 'PAY-'.now()->format('Ym').'-'.strtoupper(Str::random(7)),
            'status' => 'completed',
            'paid_at' => now(),
        ]);
        return $payment;
    }

    public function allocate(Payment $payment, Invoice $invoice, float $amount): Payment
    {
        if ($payment->status !== 'completed') {
            throw ValidationException::withMessages(['payment' => ['Only completed payments can be allocated.']]);
        }
        if ($payment->customer_id !== $invoice->customer_id) {
            throw ValidationException::withMessages(['invoice' => ['Payment and invoice belong to different customers.']]);
        }
        if ($amount <= 0 || $amount > (float) $invoice->amount_due) {
            throw ValidationException::withMessages(['amount' => ['Allocation must be greater than zero and no more than the invoice amount due.']]);
        }

        $alreadyAllocated = (float) $payment->allocations()->sum('amount');
        if ($alreadyAllocated + $amount > (float) $payment->amount) {
            throw ValidationException::withMessages(['amount' => ['Allocation exceeds the unallocated payment balance.']]);
        }

        $payment->allocations()->create(['invoice_id' => $invoice->id, 'amount' => $amount]);
        $newPaid = (float) $invoice->amount_paid + $amount;
        $newDue = max(0, (float) $invoice->total_amount - $newPaid);
        $invoice->update([
            'amount_paid' => $newPaid,
            'amount_due' => $newDue,
            'status' => $newDue <= 0 ? 'paid' : 'partially_paid',
        ]);
        return $payment->fresh('allocations');
    }
}
