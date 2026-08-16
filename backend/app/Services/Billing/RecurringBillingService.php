<?php
namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Support\Str;
use RuntimeException;

class RecurringBillingService
{
    public function generate(Subscription $subscription): Invoice
    {
        $subscription->loadMissing(['customer','package']);
        $customer=$subscription->customer;
        $package=$subscription->package;
        if (!$customer || !$package) {
            throw new RuntimeException('Subscription is missing customer or package.');
        }

        $periodStart=now()->startOfDay();
        $periodEnd=$periodStart->copy()->addMonth()->subDay();
        $existing=Invoice::where('customer_id',$customer->id)->whereDate('period_start',$periodStart)->first();
        if ($existing) return $existing;

        $total=(float)$package->price;
        return Invoice::create([
            'invoice_number'=>'INV-'.now()->format('Ym').'-'.Str::upper(Str::random(7)),
            'customer_id'=>$customer->id,
            'subscription_id'=>$subscription->id,
            'invoice_date'=>$periodStart,
            'subtotal'=>$total,
            'discount_amount'=>0,
            'tax_amount'=>0,
            'total_amount'=>$total,
            'amount_paid'=>0,
            'amount_due'=>$total,
            'status'=>'issued',
            'due_date'=>$periodStart->copy()->addDays(7),
            'period_start'=>$periodStart,
            'period_end'=>$periodEnd,
            'currency'=>'KES',
        ]);
    }
}
