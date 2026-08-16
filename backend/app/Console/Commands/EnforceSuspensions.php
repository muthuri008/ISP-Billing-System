<?php
namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\Billing\ServiceEnforcementService;
use Illuminate\Console\Command;

class EnforceSuspensions extends Command
{
    protected $signature='billing:enforce-suspensions {--grace=3}';
    protected $description='Suspend services for invoices beyond the grace period';

    public function handle(ServiceEnforcementService $enforcement): int
    {
        $grace=(int)$this->option('grace'); $count=0;
        Invoice::query()->where('status','overdue')->where('amount_due','>',0)->whereDate('due_date','<=',now()->subDays($grace)->startOfDay())->chunkById(100,function($invoices)use($enforcement,&$count){foreach($invoices as $invoice){if($enforcement->suspendForInvoice($invoice)){$invoice->update(['status'=>'suspended']);$count++;}}});
        $this->info("Suspended {$count} services."); return self::SUCCESS;
    }
}
