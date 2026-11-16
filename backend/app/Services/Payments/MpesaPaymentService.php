<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Billing\InvoiceSettlementService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MpesaPaymentService
{
    public function __construct(
        private readonly MpesaGateway $gateway,
        private readonly InvoiceSettlementService $settlements,
    ) {}

    public function initiateInvoiceStkPush(\App\Models\Invoice $invoice, string $phone): array
    {
        if ((float) $invoice->amount_due <= 0 || $invoice->status === 'paid') {
            throw new RuntimeException('Invoice has no outstanding balance.');
        }

        if (abs((float) $invoice->amount_due - round((float) $invoice->amount_due)) > 0.0001) {
            throw new RuntimeException('M-Pesa STK Push supports whole KES amounts only. Contact support to arrange payment for this invoice.');
        }

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

            $response = $this->gateway->stkPush(
                $phone,
                (float) $invoice->amount_due,
                $invoice->invoice_number,
                'ISP invoice payment',
            );
            $checkoutId = data_get($response, 'CheckoutRequestID');

            if (! is_string($checkoutId) || $checkoutId === '') {
                throw new RuntimeException('M-Pesa did not return a CheckoutRequestID.');
            }

            $payment->update([
                'external_reference' => $checkoutId,
                'metadata' => array_merge($payment->metadata ?? [], ['stk_response' => $response]),
            ]);

            return ['payment' => $payment->fresh(), 'stk' => $response];
        });
    }

    public function recordCallback(array $payload): ?Payment
    {
        $callback = data_get($payload, 'Body.stkCallback');
        $checkoutId = data_get($callback, 'CheckoutRequestID');

        if (! is_array($callback) || ! is_string($checkoutId) || $checkoutId === '') {
            return null;
        }

        return DB::transaction(function () use ($callback, $payload, $checkoutId) {
            $payment = Payment::query()
                ->where('method', 'mpesa')
                ->where('external_reference', $checkoutId)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                return null;
            }

            // A completed payment is immutable under callback retries.
            if ($payment->status === 'completed') {
                return $payment->fresh('allocations.invoice');
            }

            $resultCode = data_get($callback, 'ResultCode');
            if (! is_numeric($resultCode)) {
                throw new RuntimeException('M-Pesa callback is missing a valid ResultCode.');
            }

            // Daraja callbacks are not cryptographically signed. Confirm the checkout
            // request directly with Safaricom before changing a payment's financial state.
            $verification = $this->gateway->queryStkPush($checkoutId);
            $verifiedResultCode = data_get($verification, 'ResultCode');

            if (! is_numeric($verifiedResultCode) || (int) $verifiedResultCode !== (int) $resultCode) {
                throw new RuntimeException('Safaricom transaction query did not confirm the callback result.');
            }

            if ((int) $resultCode !== 0) {
                $payment->update([
                    'status' => 'failed',
                    'metadata' => array_merge($payment->metadata ?? [], ['callback' => $payload]),
                ]);

                return $payment->fresh();
            }

            $items = collect(data_get($callback, 'CallbackMetadata.Item', []))->keyBy('Name');
            $receipt = data_get($items->get('MpesaReceiptNumber'), 'Value');
            $callbackAmount = data_get($items->get('Amount'), 'Value');
            $callbackPhone = data_get($items->get('PhoneNumber'), 'Value');

            if (! is_string($receipt) || trim($receipt) === '' || ! is_numeric($callbackAmount) || ! is_numeric($callbackPhone)) {
                throw new RuntimeException('Successful M-Pesa callback is missing its receipt, amount or phone number.');
            }

            $requestedPhone = preg_replace('/\\D+/', '', (string) data_get($payment->metadata, 'phone')) ?? '';
            $confirmedPhone = preg_replace('/\\D+/', '', (string) $callbackPhone) ?? '';
            if ($requestedPhone === '' || substr($requestedPhone, -9) !== substr($confirmedPhone, -9)) {
                throw new RuntimeException('M-Pesa callback phone number does not match the STK request.');
            }

            $amount = round((float) $callbackAmount, 2);
            $requestedAmount = round((float) $payment->amount, 2);

            // Daraja STK Push accepts whole-shilling amounts. Match that exact
            // amount; never silently accept a smaller or larger payment.
            if ($amount <= 0 || $amount !== round($requestedAmount)) {
                throw new RuntimeException('M-Pesa callback amount does not match the requested STK amount.');
            }

            $receiptAlreadyUsed = Payment::query()
                ->where('transaction_reference', $receipt)
                ->where('id', '!=', $payment->getKey())
                ->exists();

            if ($receiptAlreadyUsed) {
                throw new RuntimeException('This M-Pesa receipt has already been applied to another payment.');
            }

            $payment->update([
                'status' => 'completed',
                'amount' => $amount,
                'transaction_reference' => $receipt,
                'paid_at' => now(),
                'metadata' => array_merge($payment->metadata ?? [], ['callback' => $payload]),
            ]);

            $invoiceId = data_get($payment->metadata, 'invoice_id');
            if ($invoiceId) {
                $invoice = Invoice::query()
                    ->where('customer_id', $payment->customer_id)
                    ->findOrFail($invoiceId);

                $this->settlements->allocate($payment, $invoice);

                return $payment->fresh('allocations.invoice');
            }

            return $this->settlements->settlePayment($payment);
        });
    }
}
