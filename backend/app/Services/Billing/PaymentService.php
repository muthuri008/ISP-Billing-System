<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function record(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $amount=(float)($data['amount'] ?? 0);
            if($amount<=0) throw ValidationException::withMessages(['amount'=>['Payment amount must be greater than zero.']]);
            return Payment::create([
                ...$data,
                'payment_number' => 'PAY-'.now()->format('Ym').'-'.strtoupper(Str::random(7)),
                'status' => $data['status'] ?? 'completed',
                'paid_at' => $data['paid_at'] ?? now(),
            ]);
        });
    }

    public function allocate(Payment $payment, Invoice $invoice, float $amount): Payment
    {
        return DB::transaction(function () use ($payment, $invoice, $amount) {
            app(InvoiceSettlementService::class)->allocate($payment, $invoice, $amount);

            return $payment->fresh('allocations.invoice');
        });
    }
}
