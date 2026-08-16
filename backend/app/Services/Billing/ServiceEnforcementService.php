<?php
namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\RadiusUser;
use App\Models\ServiceAccount;
use App\Services\Radius\RadiusService;
use Illuminate\Support\Facades\Log;

class ServiceEnforcementService
{
    public function __construct(private readonly RadiusService $radius) {}

    public function suspendForInvoice(Invoice $invoice): bool
    {
        $service=ServiceAccount::where('subscription_id',$invoice->subscription_id)->where('customer_id',$invoice->customer_id)->first();
        if (!$service) return false;
        $user=RadiusUser::where('service_account_id',$service->id)->first();
        if (!$user) return false;
        try {
            $this->radius->suspend($user);
            return true;
        } catch (\Throwable $e) {
            Log::error('RADIUS suspension failed',['invoice_id'=>$invoice->id,'service_account_id'=>$service->id,'error'=>$e->getMessage()]);
            return false;
        }
    }

    public function restoreForPayment(Invoice $invoice): bool
    {
        if ((float) $invoice->amount_due > 0) return false;
        $service=ServiceAccount::where('subscription_id',$invoice->subscription_id)->where('customer_id',$invoice->customer_id)->first();
        if (!$service) return false;
        $user=RadiusUser::where('service_account_id',$service->id)->first();
        if (!$user) return false;
        try {
            $this->radius->activate($user);
            return true;
        } catch (\Throwable $e) {
            Log::error('RADIUS restoration failed',['invoice_id'=>$invoice->id,'service_account_id'=>$service->id,'error'=>$e->getMessage()]);
            return false;
        }
    }
}
