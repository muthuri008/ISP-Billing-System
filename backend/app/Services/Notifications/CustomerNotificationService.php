<?php

namespace App\Services\Notifications;

use App\Models\Customer;
use Illuminate\Support\Facades\Log;

class CustomerNotificationService
{
    public function paymentReceived(Customer $customer, string $receipt, string $amount): void
    {
        Log::info('customer.payment_received', [
            'customer_id' => $customer->id,
            'receipt' => $receipt,
            'amount' => $amount,
        ]);
    }

    public function invoiceIssued(Customer $customer, string $invoiceNumber, string $amountDue): void
    {
        Log::info('customer.invoice_issued', [
            'customer_id' => $customer->id,
            'invoice_number' => $invoiceNumber,
            'amount_due' => $amountDue,
        ]);
    }
}
