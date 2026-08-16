<?php

namespace App\Services\Network;

use App\Models\Invoice;
use App\Models\ServiceAccount;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class BillingServiceLifecycleService
{
    public function __construct(
        private readonly ServiceLifecycleService $lifecycle,
        private readonly AuditLogger $audit,
    ) {}

    public function suspendForBilling(ServiceAccount $account, Invoice $invoice): ServiceAccount
    {
        return DB::transaction(function () use ($account, $invoice) {
            $metadata = $account->provisioning_metadata ?? [];
            $metadata['billing_suspension'] = [
                'reason' => 'overdue_invoice',
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'suspended_at' => now()->toIso8601String(),
            ];
            $account->update(['provisioning_metadata' => $metadata]);
            $account = $this->lifecycle->suspend($account);
            $this->audit->log('service.suspended_for_billing', 'service_account', $account->id, $metadata['billing_suspension']);
            return $account->fresh();
        });
    }

    public function restoreAfterBilling(ServiceAccount $account, Invoice $invoice): ?ServiceAccount
    {
        $metadata = $account->provisioning_metadata ?? [];
        $reason = $metadata['billing_suspension'] ?? null;
        if (!$reason || (int)($reason['invoice_id'] ?? 0) !== (int)$invoice->id) {
            return null;
        }

        return DB::transaction(function () use ($account, $metadata, $invoice) {
            unset($metadata['billing_suspension']);
            $account->update(['provisioning_metadata' => $metadata]);
            $account = $this->lifecycle->restore($account);
            $this->audit->log('service.restored_after_billing', 'service_account', $account->id, [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ]);
            return $account->fresh();
        });
    }
}
