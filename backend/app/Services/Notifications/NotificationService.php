<?php
namespace App\Services\Notifications;

use App\Models\Customer;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function invoiceDueSoon(Customer $customer, array $context=[]): void
    {
        $this->log('invoice.due_soon',$customer,$context);
    }

    public function paymentReceived(Customer $customer, array $context=[]): void
    {
        $this->log('payment.received',$customer,$context);
    }

    public function serviceSuspended(Customer $customer, array $context=[]): void
    {
        $this->log('service.suspended',$customer,$context);
    }

    public function serviceRestored(Customer $customer, array $context=[]): void
    {
        $this->log('service.restored',$customer,$context);
    }

    private function log(string $event, Customer $customer, array $context): void
    {
        Log::info('notification.'.$event,['customer_id'=>$customer->id,'event'=>$event,'context'=>$context]);
    }
}
