<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InvoiceSettlementService
{
    public function allocate(Payment $payment, Invoice $invoice, ?float $amount = null): PaymentAllocation
    {
        return DB::transaction(function () use ($payment, $invoice, $amount) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if ($payment->status !== 'completed') {
                throw new RuntimeException('Only completed payments can be allocated.');
            }
            if ($payment->customer_id !== $invoice->customer_id) {
                throw new RuntimeException('Payment and invoice belong to different customers.');
            }

            $alreadyAllocated = (float) PaymentAllocation::where('payment_id', $payment->id)->sum('amount');
            $invoicePaid = (float) PaymentAllocation::where('invoice_id', $invoice->id)->sum('amount');
            $paymentRemaining = (float) $payment->amount - $alreadyAllocated;
            $invoiceRemaining = (float) $invoice->total_amount - $invoicePaid;
            $allocation = $amount === null ? min($paymentRemaining, $invoiceRemaining) : $amount;

            if ($allocation <= 0) throw new RuntimeException('No amount remains to allocate.');
            if ($allocation > $paymentRemaining || $allocation > $invoiceRemaining) {
                throw new RuntimeException('Allocation exceeds the available payment or invoice balance.');
            }

            $record = PaymentAllocation::create(['payment_id' => $payment->id, 'invoice_id' => $invoice->id, 'amount' => $allocation]);
            $newPaid = $invoicePaid + $allocation;
            $invoice->update([
                'amount_paid' => $newPaid,
                'amount_due' => max(0, (float) $invoice->total_amount - $newPaid),
                'status' => $newPaid >= (float) $invoice->total_amount ? 'paid' : 'partial',
            ]);

            return $record;
        });
    }

    public function settlePayment(Payment $payment): Payment
    {
        $payment->loadMissing('allocations');
        $remaining = (float) $payment->amount - (float) $payment->allocations->sum('amount');
        if ($remaining <= 0) return $payment->fresh('allocations.invoice');

        $invoices = Invoice::where('customer_id', $payment->customer_id)
            ->where('amount_due', '>', 0)
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        foreach ($invoices as $invoice) {
            if ($remaining <= 0) break;
            $allocation = min($remaining, (float) $invoice->amount_due);
            $this->allocate($payment, $invoice, $allocation);
            $remaining -= $allocation;
        }

        return $payment->fresh('allocations.invoice');
    }
}
