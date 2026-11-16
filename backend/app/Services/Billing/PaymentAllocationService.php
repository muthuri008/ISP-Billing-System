<?php
namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentAllocationService
{
    public function allocate(Payment $payment, ?Invoice $invoice=null): float
    {
        return DB::transaction(function() use ($payment,$invoice) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($payment->status !== 'completed') throw new RuntimeException('Only completed payments can be allocated.');
            $remaining=(float)$payment->amount-(float)$payment->allocations()->sum('amount');
            if($remaining<=0)return 0.0;
            $query=Invoice::query()->where('customer_id',$payment->customer_id)->whereIn('status',['issued','partially_paid','overdue'])->where('amount_due','>',0)->orderBy('due_date')->lockForUpdate();
            if($invoice)$query->whereKey($invoice->id);
            $allocated=0.0;
            foreach($query->get() as $target){
                $amount=min($remaining,(float)$target->amount_due);
                if($amount<=0)continue;
                PaymentAllocation::create(['payment_id'=>$payment->id,'invoice_id'=>$target->id,'amount'=>$amount]);
                $target->amount_paid=(float)$target->amount_paid+$amount;
                $target->amount_due=max(0,(float)$target->total_amount-(float)$target->amount_paid);
                $target->status=$target->amount_due<=0?'paid':'partially_paid';
                $target->save();
                $allocated+=$amount;$remaining-=$amount;
                if($remaining<=0)break;
            }
            return $allocated;
        });
    }
}
