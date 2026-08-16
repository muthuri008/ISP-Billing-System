<?php

namespace App\Services\Network;

use App\Models\ServiceAccount;
use App\Models\Subscription;
use Illuminate\Support\Str;
use RuntimeException;

class RadiusProvisioningService
{
    public function __construct(private readonly RouterClientFactory $clients) {}

    public function provision(Subscription $subscription, ?int $routerId = null): ServiceAccount
    {
        $subscription->loadMissing(['customer', 'package']);
        $account = ServiceAccount::firstOrNew(['subscription_id' => $subscription->id]);
        $isNew = !$account->exists;
        $account->customer_id = $subscription->customer_id;
        $account->router_id = $routerId ?? $account->router_id;
        if (!$account->router_id) throw new RuntimeException('A router is required before network provisioning.');
        $account->username = $account->username ?: 'st-' . Str::lower($subscription->subscription_number);
        if ($isNew) $account->password_hash = Str::random(16);
        $account->access_type = $account->access_type ?: 'pppoe';
        $account->status = $subscription->status === 'active' ? 'active' : 'suspended';
        $account->radius_profile = $subscription->package->code;
        $account->last_provisioned_at = now();
        $account->provisioning_metadata = array_merge($account->provisioning_metadata ?? [], [
            'radius' => ['profile'=>$subscription->package->code,'download_mbps'=>$subscription->package->download_mbps,'upload_mbps'=>$subscription->package->upload_mbps,'enabled'=>$account->status==='active'],
        ]);
        $account->save();
        $this->clients->for($account->router)->createPppSecret($account->username, $account->password_hash, $subscription->package->code, $account->status !== 'active');
        return $account->fresh();
    }

    public function setAccessState(ServiceAccount $account, bool $enabled): ServiceAccount
    {
        if ($account->router && $account->access_type === 'pppoe') {
            $this->clients->for($account->router)->setUserDisabled($account->username, !$enabled);
        }
        $account->status = $enabled ? 'active' : 'suspended';
        $meta = $account->provisioning_metadata ?? [];
        $meta['radius']['enabled'] = $enabled;
        $account->provisioning_metadata = $meta;
        $account->last_provisioned_at = now();
        $account->save();
        return $account->fresh();
    }
}
