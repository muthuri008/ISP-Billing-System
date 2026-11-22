<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Network\BillingServiceLifecycleService;
use Illuminate\Support\Facades\DB;

class PaymentServiceV2
{
    public function __construct(
        private readonly InvoiceSettlementService $settlements,
        private readonly BillingServiceLifecycleService $billingLifecycle,
    ) {}

    public function allocate(Payment $payment, Invoice $invoice, float $amount): Payment
    {
        return DB::transaction(function () use ($payment, $invoice, $amount) {
            // Use the same guarded settlement engine as M-Pesa callbacks so
            // manual allocations cannot bypass invoice-state or currency rules.
            $this->settlements->allocate($payment, $invoice, $amount);

            $invoice->refresh();
            if ($invoice->status === 'paid') {
                $invoice->loadMissing('customer.serviceAccounts');

                foreach ($invoice->customer->serviceAccounts as $account) {
                    if ($account->status === 'suspended') {
                        $this->billingLifecycle->restoreAfterBilling($account, $invoice);
                    }
                }
            }

            return $payment->fresh('allocations.invoice');
        });
    }
}
