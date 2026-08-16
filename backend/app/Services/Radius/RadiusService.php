<?php
namespace App\Services\Radius;

use App\Models\Customer;
use App\Models\Subscription;
use App\Models\RadiusUser;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class RadiusService
{
    public function provision(Customer $customer, Subscription $subscription, string $password): RadiusUser
    {
        $username=$customer->customer_number ?? 'cust-'.$customer->id;
        $attributes=['subscription_id'=>$subscription->id,'package_id'=>$subscription->package_id];
        return RadiusUser::updateOrCreate(['customer_id'=>$customer->id],['username'=>$username,'password_hash'=>Hash::make($password),'status'=>'active','attributes'=>$attributes]);
    }

    public function suspend(RadiusUser $user): RadiusUser { $user->update(['status'=>'suspended']); return $user->fresh(); }
    public function activate(RadiusUser $user): RadiusUser { $user->update(['status'=>'active']); return $user->fresh(); }
    public function disconnect(RadiusUser $user): bool { return $user->update(['status'=>'disconnected']); }
}
