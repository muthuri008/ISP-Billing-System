<?php

namespace App\Services\Network;

use App\Models\ServiceAccount;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ServiceLifecycleService
{
    public function __construct(
        private readonly ServiceAccessService $access,
        private readonly RadiusPolicyService $radius,
    ) {}

    public function suspend(ServiceAccount $account): ServiceAccount
    {
        return DB::transaction(function () use ($account) {
            $account = $this->access->suspend($account);
            $this->radius->setStatus($account, 'suspended');
            return $account->fresh();
        });
    }

    public function restore(ServiceAccount $account): ServiceAccount
    {
        return DB::transaction(function () use ($account) {
            $account = $this->access->activate($account);
            $this->radius->setStatus($account, 'active');
            return $account->fresh();
        });
    }

    public function provision(ServiceAccount $account): ServiceAccount
    {
        if ($account->status === 'disabled') {
            throw new RuntimeException('Disabled service accounts must be re-provisioned before activation.');
        }

        return DB::transaction(function () use ($account) {
            $this->radius->provision($account);
            $account = $this->access->activate($account);
            return $account->fresh();
        });
    }
}
