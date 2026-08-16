<?php

namespace App\Services\Network;

use App\Models\ServiceAccount;
use App\Models\Subscription;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class RadiusProvisioningService
{
    public function provision(Subscription $subscription, ?int $routerId = null): ServiceAccount
    {
        $subscription->loadMissing(['customer', 'package']);
        $account = ServiceAccount::firstOrNew(['subscription_id' => $subscription->id]);
        $account->customer_id = $subscription->customer_id;
        $account->router_id = $routerId ?? $account->router_id;
        $account->username = $account->username ?: 'st-' . Str::lower($subscription->subscription_number);
        $plainPassword = Str::random(14);
        if (!$account->exists) $account->password_hash = Hash::make($plainPassword);
        $account->access_type = $account->access_type ?: 'pppoe';
        $account->status = $subscription->status === 'active' ? 'active' : 'suspended';
        $account->ip_address = $account->ip_address;
        $account->provisioning_metadata = array_merge($account->provisioning_metadata ?? [], [
            'radius' => [
                'username' => $account->username,
                'profile' => $subscription->package->code,
                'download_mbps' => $subscription->package->download_mbps,
                'upload_mbps' => $subscription->package->upload_mbps,
                'enabled' => $account->status === 'active',
            ],
            'credentials_generated' => !$account->exists,
        ]);
        $account->save();
        return $account;
    }

    public function setAccessState(ServiceAccount $account, bool $enabled): ServiceAccount
    {
        $account->status = $enabled ? 'active' : 'suspended';
        $meta = $account->provisioning_metadata ?? [];
        $meta['radius']['enabled'] = $enabled;
        $account->provisioning_metadata = $meta;
        $account->save();
        return $account;
    }
}
