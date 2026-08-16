<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Billing\InvoiceSettlementService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MpesaPaymentService
{
    public function __construct(private readonly MpesaGateway $gateway, private readonly InvoiceSettlementService $settlements) {}

    public function initiateInvoiceStkPush(Invoice $invoice, string $phone): array
    {
        if ((float) $invoice->amount_due <= 0 || $invoice->status === 'paid') throw new RuntimeException('Invoice has no outstanding balance.');

        return DB::transaction(function () use ($invoice, $phone) {
            $payment = Payment::create([
                'payment_number' => 'PAY-'.now()->format('YmdHis').'-'.str()->upper(str()->random(6)),
                'customer_id' => $invoice->customer_id,
                'method' => 'mpesa',
                'amount' => $invoice->amount_due,
                'currency' => 'KES',
                'status' => 'pending',
                'notes' => 'M-Pesa STK payment for invoice '.$invoice->invoice_number,
                'metadata' => ['invoice_id' => $invoice->id, 'phone' => $phone],
            ]);
            $response = $this->gateway->stkPush($phone, (float) $invoice->amount_due, $invoice->invoice_number, 'ISP invoice payment');
            $checkoutId = data_get($response, 'CheckoutRequestID');
            if (! $checkoutId) throw new RuntimeException('M-Pesa did not return a CheckoutRequestID.');
            $payment->update(['external_reference' => $checkoutId, 'metadata' => array_merge($payment->metadata ?? [], ['stk_response' => $response])]);
            return ['payment' => $payment->fresh(), 'stk' => $response];
        });
    }

    public function initiateStkPush(string $phone, float $amount, string $accountReference, string $description): array
    {
        return $this->gateway->stkPush($phone, $amount, $accountReference, $description);
    }

    public function recordCallback(array $payload): ?Payment
    {
        $callback = data_get($payload, 'Body.stkCallback');
        if (! $callback) return null;
        $checkoutId = data_get($callback, 'CheckoutRequestID');
        if (! $checkoutId) return null;

        return DB::transaction(function () use ($callback, $payload, $checkoutId) {
            $payment = Payment::where('external_reference', $checkoutId)->lockForUpdate()->first();
            if (! $payment) return null;
            if ($payment->status === 'completed') return $payment->fresh('allocations.invoice');
            if ((int) data_get($callback, 'ResultCode', 1) !== 0) {
                $payment->update(['status' => 'failed', 'metadata' => $payload]);
                return $payment->fresh();
            }

            $items = collect(data_get($callback, 'CallbackMetadata.Item', []))->keyBy('Name');
            $receipt = data_get($items->get('MpesaReceiptNumber'), 'Value');
            $amount = (float) data_get($items->get('Amount'), 'Value', $payment->amount);
            if ($amount <= 0) throw new RuntimeException('M-Pesa callback returned an invalid amount.');
            if ($amount > (float) $payment->amount) throw new RuntimeException('M-Pesa callback amount exceeds the requested payment.');

            $payment->update(['status' => 'completed', 'amount' => $amount, 'transaction_reference' => $receipt, 'paid_at' => now(), 'metadata' => $payload]);
            return $this->settlements->settlePayment($payment);
        });
    }
}
