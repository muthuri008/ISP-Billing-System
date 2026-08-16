<?php
namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendBillingNotifications extends Command
{
    protected $signature='billing:notify {--days=2}';
    protected $description='Queue billing notification events for customers';

    public function handle(): int
    {
        $days=(int)$this->option('days');
        $due=Invoice::query()->whereIn('status',['issued','partial'])->whereDate('due_date',now()->addDays($days)->toDateString())->with('customer')->get();
        foreach($due as $invoice){Log::info('billing.notification.due_soon',['invoice_id'=>$invoice->id,'customer_id'=>$invoice->customer_id,'days'=>$days]);}
        $this->info("Queued {$due->count()} due-soon notifications.");
        return self::SUCCESS;
    }
}
