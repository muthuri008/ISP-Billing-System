<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\Billing\InvoiceGenerationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateRecurringInvoices extends Command
{
    // Kept as a compatibility alias; the scheduled canonical command is
    // billing:generate-invoices. Both commands now use the same billing rules.
    protected $signature = 'billing:generate-recurring-invoices {--date= : Billing date in Y-m-d format}';
    protected $description = 'Generate recurring invoices using the canonical billing engine';

    public function handle(InvoiceGenerationService $generator): int
    {
        $date = $this->option('date')
            ? Carbon::createFromFormat('Y-m-d', $this->option('date'))->startOfDay()
            : now()->startOfDay();

        $generated = 0;

        Subscription::query()
            ->with('package')
            ->where('status', 'active')
            ->where('auto_renew', true)
            ->chunkById(100, function ($subscriptions) use ($generator, $date, &$generated): void {
                foreach ($subscriptions as $subscription) {
                    if ($generator->generateForSubscription($subscription, $date)) {
                        $generated++;
                    }
                }
            });

        $this->info("Processed recurring billing. Generated/confirmed {$generated} invoice(s).");

        return self::SUCCESS;
    }
}
