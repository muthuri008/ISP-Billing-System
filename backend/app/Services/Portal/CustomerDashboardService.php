<?php

namespace App\Services\Portal;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ServiceAccount;

class CustomerDashboardService
{
    public function summary(Customer $customer): array
    {
        $invoices = Invoice::where('customer_id', $customer->id)->orderByDesc('due_date');
        $payments = Payment::where('customer_id', $customer->id)
            ->where('status', 'completed')
            ->orderByDesc('paid_at');
        $services = ServiceAccount::where('customer_id', $customer->id)->get();

        $outstandingByCurrency = (clone $invoices)
            ->where('amount_due', '>', 0)
            ->selectRaw('currency, SUM(amount_due) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->map(fn ($amount): float => (float) $amount);

        $overdueByCurrency = (clone $invoices)
            ->where('status', 'overdue')
            ->where('amount_due', '>', 0)
            ->selectRaw('currency, SUM(amount_due) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->map(fn ($amount): float => (float) $amount);

        return [
            'customer' => [
                'id' => $customer->id,
                'customer_number' => $customer->customer_number,
                'name' => $customer->full_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'status' => $customer->status,
            ],
            'billing' => [
                // Never combine balances denominated in different currencies.
                'outstanding_by_currency' => $outstandingByCurrency,
                'overdue_by_currency' => $overdueByCurrency,
                'open_invoices' => (clone $invoices)->where('amount_due', '>', 0)->count(),
            ],
            'services' => $services->map(fn ($service) => [
                'id' => $service->id,
                'username' => $service->username,
                'status' => $service->status,
                'router_id' => $service->router_id,
            ])->values(),
            'recent_payments' => $payments->limit(5)->get([
                'id', 'payment_number', 'amount', 'currency', 'method', 'transaction_reference', 'paid_at',
            ]),
            'recent_invoices' => $invoices->limit(5)->get([
                'id', 'invoice_number', 'total_amount', 'amount_paid', 'amount_due', 'currency', 'status', 'due_date',
            ]),
        ];
    }
}
