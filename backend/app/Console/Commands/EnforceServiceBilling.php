<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\ServiceAccount;
use App\Services\Audit\AuditLogger;
use App\Services\Network\ServiceLifecycleService;
use Illuminate\Console\Command;

class EnforceServiceBilling extends Command
{
    protected $signature = 'billing:enforce-service {--dry-run : Report accounts without changing network state}';
    protected $description = 'Suspend service accounts whose billing is overdue beyond the grace period';

    public function handle(ServiceLifecycleService $lifecycle, AuditLogger $audit): int
    {
        $count = 0;
        ServiceAccount::query()->where('status', 'active')->chunkById(100, function ($accounts) use ($lifecycle, $audit, &$count) {
            foreach ($accounts as $account) {
                $hasOverdue = Invoice::query()
                    ->where('customer_id', $account->customer_id)
                    ->where('status', '!=', 'paid')
                    ->whereDate('due_date', '<', today())
                    ->exists();

                if (! $hasOverdue) continue;
                $count++;

                if ($this->option('dry-run')) {
                    $this->line("Would suspend service account {$account->id}");
                    continue;
                }

                $lifecycle->suspend($account);
                $audit->log('service.suspended_for_billing', 'service_account', $account->id);
            }
        });

        $this->info(($this->option('dry-run') ? 'Would suspend ' : 'Suspended ') . $count . ' service account(s).');
        return self::SUCCESS;
    }
}
