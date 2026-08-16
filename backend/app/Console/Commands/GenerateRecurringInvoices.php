<?php
namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\Billing\RecurringBillingService;
use Illuminate\Console\Command;

class GenerateRecurringInvoices extends Command
{
    protected $signature='billing:generate-invoices';
    protected $description='Generate recurring invoices for active subscriptions';

    public function handle(RecurringBillingService $billing): int
    {
        $count=0;
        Subscription::query()->where('status','active')->with(['customer','package'])->chunkById(100,function($subscriptions)use($billing,&$count){foreach($subscriptions as $subscription){try{$billing->generate($subscription);$count++;}catch(\Throwable $e){$this->error("Subscription {$subscription->id}: {$e->getMessage()}");}}});
        $this->info("Processed {$count} subscriptions.");
        return self::SUCCESS;
    }
}
