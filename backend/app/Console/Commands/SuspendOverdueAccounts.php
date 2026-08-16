<?php
namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\Billing\ServiceLifecycleService;
use Illuminate\Console\Command;

class SuspendOverdueAccounts extends Command
{
    protected $signature = 'billing:suspend-overdue';
    protected $description = 'Suspend network services for customers with overdue invoices';

    public function handle(ServiceLifecycleService $lifecycle): int
    {
        Invoice::with('customer.serviceAccounts')
            ->whereIn('status', ['issued', 'partially_paid'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->chunkById(100, function ($invoices) use ($lifecycle) {
                foreach ($invoices as $invoice) {
                    foreach ($invoice->customer->serviceAccounts as $account) {
                        if ($account->status === 'active') {
                            $lifecycle->suspend($account);
                            $this->info("Suspended {$account->username}");
                        }
                    }
                    $invoice->update(['status' => 'overdue']);
                }
            });
        return self::SUCCESS;
    }
}
