<?php

namespace App\Services\Billing;

use App\Models\Payment;
use App\Models\ServiceAccount;
use App\Services\Audit\AuditLogger;
use App\Services\Billing\PaymentReceiptService;
use App\Services\Network\ServiceLifecycleService;
use App\Services\Notifications\CustomerNotificationService;
use Illuminate\Support\Facades\DB;

class BillingServiceLifecycle
{
    public function __construct(
        private readonly InvoiceSettlementService $settlement,
        private readonly PaymentReceiptService $receipts,
        private readonly ServiceLifecycleService $services,
        private readonly AuditLogger $audit,
        private readonly CustomerNotificationService $notifications,
    ) {}

    public function processCompletedPayment(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            $payment = $payment->fresh(['customer', 'allocations.invoice']);
            if ($payment->status !== 'completed') return $payment;

            $payment = $this->settlement->settlePayment($payment);
            $receipt = $this->receipts->issue($payment);

            $this->audit->log('payment.settled', 'payment', $payment->id, [
                'amount' => (string) $payment->amount,
                'receipt' => $receipt,
            ]);
            $this->notifications->paymentReceived($payment->customer, $receipt, (string) $payment->amount);

            foreach ($payment->allocations->load('invoice.subscription.serviceAccount')->groupBy(fn ($a) => $a->invoice?->subscription_id) as $allocations) {
                $subscription = $allocations->first()?->invoice?->subscription;
                $account = $subscription?->serviceAccount;
                if ($account && $account->status === 'suspended') {
                    $this->services->restore($account);
                    $this->audit->log('service.restored_after_payment', 'service_account', $account->id, ['payment_id' => $payment->id]);
                }
            }

            return $payment->fresh(['allocations.invoice']);
        });
    }
}
