<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Network\BillingServiceLifecycleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentServiceV2
{
    public function __construct(private readonly BillingServiceLifecycleService $billingLifecycle) {}

    public function allocate(Payment $payment, Invoice $invoice, float $amount): Payment
    {
        return DB::transaction(function () use ($payment,$invoice,$amount) {
            $payment->refresh();$invoice->refresh();
            if($payment->status!=='completed')throw ValidationException::withMessages(['payment'=>['Only completed payments can be allocated.']]);
            if($payment->customer_id!==$invoice->customer_id)throw ValidationException::withMessages(['invoice'=>['Payment and invoice belong to different customers.']]);
            if($amount<=0||$amount>(float)$invoice->amount_due)throw ValidationException::withMessages(['amount'=>['Invalid invoice allocation amount.']]);
            if((float)$payment->allocations()->sum('amount')+$amount>(float)$payment->amount)throw ValidationException::withMessages(['amount'=>['Allocation exceeds the unallocated payment balance.']]);
            $payment->allocations()->create(['invoice_id'=>$invoice->id,'amount'=>$amount]);
            $newPaid=(float)$invoice->amount_paid+$amount;$newDue=max(0,(float)$invoice->total_amount-$newPaid);
            $invoice->update(['amount_paid'=>$newPaid,'amount_due'=>$newDue,'status'=>$newDue<=0?'paid':'partial']);
            if($newDue<=0){$invoice->loadMissing('customer.serviceAccounts');foreach($invoice->customer->serviceAccounts as $account){if($account->status==='suspended')$this->billingLifecycle->restoreAfterBilling($account,$invoice);}}
            return $payment->fresh('allocations');
        });
    }
}
