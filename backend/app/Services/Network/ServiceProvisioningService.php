<?php

namespace App\Services\Network;

use App\Models\ServiceAccount;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ServiceProvisioningService
{
    public function __construct(
        private readonly RadiusPolicyService $radius,
        private readonly ServiceAccessService $access,
    ) {}

    public function provision(ServiceAccount $account): ServiceAccount
    {
        if (! in_array($account->access_type, ['pppoe', 'hotspot'], true)) {
            throw new InvalidArgumentException('Unsupported access type. Use pppoe or hotspot.');
        }

        return DB::transaction(function () use ($account) {
            $this->radius->provision($account);
            $account = $this->access->activate($account);
            return $account->fresh();
        });
    }
}
