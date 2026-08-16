<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\ServiceAccount;
use App\Services\Network\BillingServiceLifecycleService;
use Illuminate\Console\Command;

class RestoreBillingServices extends Command
{
    protected $signature = 'billing:restore-services {--dry-run : Report restorations without changing network state}';
    protected $description = 'Restore only service accounts that were suspended for a now-paid invoice';

    public function handle(BillingServiceLifecycleService $billingLifecycle): int
    {
        $restored = 0;
        Invoice::query()->where('status', 'paid')->with('customer.serviceAccounts')->chunkById(100, function ($invoices) use ($billingLifecycle, &$restored) {
            foreach ($invoices as $invoice) {
                foreach ($invoice->customer->serviceAccounts as $account) {
                    $reason = ($account->provisioning_metadata ?? [])['billing_suspension'] ?? null;
                    if ($account->status !== 'suspended' || !$reason || (int)($reason['invoice_id'] ?? 0) !== (int)$invoice->id) continue;
                    if ($this->option('dry-run')) { $this->line("Would restore {$account->username} for {$invoice->invoice_number}."); continue; }
                    $billingLifecycle->restoreAfterBilling($account, $invoice);
                    $restored++;
                }
            }
        });
        $this->info("Restored {$restored} billing-suspended service account(s).");
        return self::SUCCESS;
    }
}
