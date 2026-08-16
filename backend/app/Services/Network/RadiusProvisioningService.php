<?php

namespace App\Services\Network;

use App\Models\ServiceAccount;
use App\Models\Subscription;
use Illuminate\Support\Str;

class RadiusProvisioningService
{
    public function provision(Subscription $subscription, ?int $routerId = null): ServiceAccount
    {
        $subscription->loadMissing(['customer', 'package']);
        $account = ServiceAccount::firstOrNew(['subscription_id' => $subscription->id]);
        $isNew = !$account->exists;
        $account->customer_id = $subscription->customer_id;
        $account->router_id = $routerId ?? $account->router_id;
        $account->username = $account->username ?: 'st-' . Str::lower($subscription->subscription_number);
        if ($isNew) $account->password_hash = Str::random(16);
        $account->access_type = $account->access_type ?: 'pppoe';
        $account->status = $subscription->status === 'active' ? 'active' : 'suspended';
        $account->radius_profile = $subscription->package->code;
        $account->last_provisioned_at = now();
        $account->provisioning_metadata = array_merge($account->provisioning_metadata ?? [], [
            'radius' => [
                'profile' => $subscription->package->code,
                'download_mbps' => $subscription->package->download_mbps,
                'upload_mbps' => $subscription->package->upload_mbps,
                'enabled' => $account->status === 'active',
            ],
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
        $account->last_provisioned_at = now();
        $account->save();
        return $account;
    }
}
