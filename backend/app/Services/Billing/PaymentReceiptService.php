<?php

namespace App\Services\Billing;

use App\Models\Payment;
use Illuminate\Support\Str;

class PaymentReceiptService
{
    public function issue(Payment $payment): string
    {
        $existing = data_get($payment->metadata, 'receipt_number');
        if ($existing) return $existing;

        $receipt = 'RCT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8));
        $metadata = $payment->metadata ?? [];
        $metadata['receipt_number'] = $receipt;
        $payment->forceFill(['metadata' => $metadata])->save();
        return $receipt;
    }
}
