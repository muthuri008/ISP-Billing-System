<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Network\BillingServiceLifecycleService;

class PaymentServiceBillingSafe
{
    public function __construct(private readonly BillingServiceLifecycleService $billingLifecycle) {}

    public function restorePaidInvoiceServices(Invoice $invoice): void
    {
        $invoice->loadMissing('customer.serviceAccounts');
        foreach ($invoice->customer->serviceAccounts as $account) {
            if ($account->status === 'suspended') {
                $this->billingLifecycle->restoreAfterBilling($account, $invoice);
            }
        }
    }
}
