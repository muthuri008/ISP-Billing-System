<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\ServiceAccount;
use App\Services\Audit\AuditLogger;
use App\Services\Network\ServiceLifecycleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EnforceBillingLifecycle extends Command
{
    protected $signature = 'billing:enforce-lifecycle {--grace=3 : Days after the invoice due date before suspension} {--dry-run : Report actions without changing billing or network state}';
    protected $description = 'Mark overdue invoices and suspend active services after the billing grace period';

    public function handle(ServiceLifecycleService $lifecycle, AuditLogger $audit): int
    {
        $grace = max(0, (int) $this->option('grace'));
        $cutoff = today()->subDays($grace);
        $processed = 0;
        $suspended = 0;

        Invoice::query()
            ->where('amount_due', '>', 0)
            ->whereIn('status', ['issued', 'partially_paid', 'overdue'])
            ->whereDate('due_date', '<=', $cutoff)
            ->with('customer')
            ->chunkById(100, function ($invoices) use ($lifecycle, $audit, &$processed, &$suspended) {
                foreach ($invoices as $invoice) {
                    $processed++;

                    $accounts = ServiceAccount::query()
                        ->where('customer_id', $invoice->customer_id)
                        ->where('status', 'active')
                        ->get();

                    if ($this->option('dry-run')) {
                        $this->line("Would mark invoice {$invoice->invoice_number} overdue and suspend {$accounts->count()} active service account(s).");
                        continue;
                    }

                    DB::transaction(function () use ($invoice, $accounts, $lifecycle, $audit, &$suspended) {
                        if ($invoice->status !== 'overdue') {
                            $invoice->update(['status' => 'overdue']);
                        }

                        foreach ($accounts as $account) {
                            $lifecycle->suspend($account);
                            $audit->log('service.suspended_for_billing', 'service_account', $account->id, [
                                'invoice_id' => $invoice->id,
                                'invoice_number' => $invoice->invoice_number,
                            ]);
                            $suspended++;
                        }
                    });
                }
            });

        $this->info("Processed {$processed} overdue invoice(s); suspended {$suspended} service account(s).");
        return self::SUCCESS;
    }
}
