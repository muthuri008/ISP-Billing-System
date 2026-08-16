<?php
namespace App\Services\Radius;

use App\Models\Customer;
use App\Models\RadiusUser;
use App\Models\ServiceAccount;
use App\Models\Subscription;

class RadiusService
{
    public function provision(Customer $customer, Subscription $subscription, string $password): RadiusUser
    {
        $service=ServiceAccount::firstOrNew(['subscription_id'=>$subscription->id]);
        $service->fill([
            'customer_id'=>$customer->id,
            'username'=>$customer->customer_number ?? 'cust-'.$customer->id,
            'password_hash'=>$password,
            'status'=>'active',
        ]);
        $service->save();

        return RadiusUser::updateOrCreate(
            ['service_account_id'=>$service->id],
            [
                'username'=>$service->username,
                'auth_type'=>'pap',
                'password'=>$password,
                'status'=>'active',
            ]
        );
    }

    public function suspend(RadiusUser $user): RadiusUser
    {
        $user->update(['status'=>'suspended']);
        if ($user->serviceAccount) $user->serviceAccount->update(['status'=>'suspended']);
        return $user->fresh();
    }

    public function activate(RadiusUser $user): RadiusUser
    {
        $user->update(['status'=>'active']);
        if ($user->serviceAccount) $user->serviceAccount->update(['status'=>'active']);
        return $user->fresh();
    }

    public function disconnect(RadiusUser $user): bool
    {
        return (bool) $user->update(['status'=>'disconnected']);
    }
}
