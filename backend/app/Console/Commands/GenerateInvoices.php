<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\Billing\InvoiceGenerationService;
use Illuminate\Console\Command;

class GenerateInvoices extends Command
{
    protected $signature = 'billing:generate-invoices {--date= : Billing date in Y-m-d format}';
    protected $description = 'Generate recurring invoices for active auto-renewing subscriptions';

    public function handle(InvoiceGenerationService $generator): int
    {
        $date = $this->option('date') ? now()->createFromFormat('Y-m-d', $this->option('date'))->startOfDay() : now()->startOfDay();
        $generated = 0;

        Subscription::with('package')->where('status', 'active')->where('auto_renew', true)->chunkById(100, function ($subscriptions) use ($generator, $date, &$generated) {
            foreach ($subscriptions as $subscription) {
                if ($generator->generateForSubscription($subscription, $date)) $generated++;
            }
        });

        $this->info("Processed recurring billing. Generated/confirmed {$generated} invoice(s).");
        return self::SUCCESS;
    }
}
