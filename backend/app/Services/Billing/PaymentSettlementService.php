<?php
namespace App\Services\Billing;

use App\Models\Invoice;
use App\Services\Billing\ServiceEnforcementService;
use Illuminate\Support\Facades\Log;

class PaymentSettlementService
{
    public function __construct(private readonly PaymentAllocationService $allocator, private readonly ServiceEnforcementService $enforcement) {}

    public function settle($payment, ?Invoice $invoice=null): float
    {
        $allocated=$this->allocator->allocate($payment,$invoice);
        if($allocated<=0)return 0.0;
        $targets=$payment->allocations()->with('invoice')->get()->pluck('invoice')->filter();
        foreach($targets as $target){
            if((float)$target->amount_due<=0 && in_array($target->status,['paid','suspended'],true)){
                try{$this->enforcement->restoreForPayment($target);}catch(\Throwable $e){Log::warning('service restoration deferred',['invoice_id'=>$target->id,'error'=>$e->getMessage()]);}
            }
        }
        return $allocated;
    }
}
