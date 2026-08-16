<?php

namespace App\Services\Payments;

use App\Models\Customer;
use App\Services\Billing\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class MpesaCallbackService
{
    public function __construct(private readonly PaymentService $payments) {}

    public function handle(array $payload): array
    {
        $reference=$payload['transaction_reference'] ?? null;
        $amount=(float)($payload['amount'] ?? 0);
        $customerId=$payload['customer_id'] ?? null;
        if(!$reference || $amount<=0 || !$customerId) throw new RuntimeException('Invalid payment callback payload.');

        return DB::transaction(function() use ($payload,$reference,$amount,$customerId) {
            $customer=Customer::findOrFail($customerId);
            $existing=\App\Models\Payment::where('transaction_reference',$reference)->lockForUpdate()->first();
            if($existing)return ['duplicate'=>true,'payment'=>$existing];
            $payment=$this->payments->record([
                'customer_id'=>$customer->id,
                'method'=>'mpesa',
                'amount'=>$amount,
                'currency'=>$payload['currency'] ?? 'KES',
                'external_reference'=>$payload['external_reference'] ?? null,
                'transaction_reference'=>$reference,
                'notes'=>'M-Pesa callback',
                'metadata'=>$payload,
            ]);
            return ['duplicate'=>false,'payment'=>$payment];
        });
    }
}
