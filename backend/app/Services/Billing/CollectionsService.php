<?php
namespace App\Services\Billing;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class CollectionsService
{
    public function processOverdue(int $graceDays=3): int
    {
        $processed=0;
        Invoice::query()->whereIn('status',['issued','partial'])->where('amount_due','>',0)->whereDate('due_date','<',now()->startOfDay())->chunkById(100,function($invoices)use(&$processed,$graceDays){foreach($invoices as $invoice){$invoice->update(['status'=>'overdue']);$eligible=$invoice->due_date?now()->greaterThan($invoice->due_date->copy()->addDays($graceDays)):false;if($eligible)DB::table('audit_logs')->insert(['user_id'=>null,'action'=>'invoice.suspension_eligible','auditable_type'=>Invoice::class,'auditable_id'=>$invoice->id,'ip_address'=>null,'user_agent'=>null,'metadata'=>json_encode(['grace_days'=>$graceDays]),'created_at'=>now(),'updated_at'=>now()]);$processed++;}});
        return $processed;
    }
}
