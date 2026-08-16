<?php

namespace App\Services\Payments;

use App\Models\Payment;

class MpesaPaymentService
{
    public function __construct(private readonly MpesaGateway $gateway) {}

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

        $payment = Payment::where('external_reference', $checkoutId)->first();
        if (! $payment) return null;
        if ((int) data_get($callback, 'ResultCode', 1) !== 0) {
            $payment->update(['status' => 'failed', 'metadata' => $payload]);
            return $payment->fresh();
        }

        $items = collect(data_get($callback, 'CallbackMetadata.Item', []))->keyBy('Name');
        $receipt = data_get($items->get('MpesaReceiptNumber'), 'Value');
        $amount = (float) data_get($items->get('Amount'), 'Value', $payment->amount);
        $payment->update([
            'status' => 'completed',
            'amount' => $amount,
            'transaction_reference' => $receipt,
            'paid_at' => now(),
            'metadata' => $payload,
        ]);
        return $payment->fresh();
    }
}
