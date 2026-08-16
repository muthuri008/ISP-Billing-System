<?php
namespace App\Services\Billing;

use App\Models\ServiceAccount;
use App\Services\Network\RadiusPolicyService;
use App\Services\Network\ServiceAccessService;
use Illuminate\Support\Facades\DB;

class ServiceLifecycleService
{
    public function __construct(private readonly ServiceAccessService $access, private readonly RadiusPolicyService $radius) {}

    public function activate(ServiceAccount $account): ServiceAccount
    {
        return DB::transaction(function () use ($account) {
            $account = $this->access->activate($account);
            $this->radius->setStatus($account, 'active');
            return $account->fresh();
        });
    }

    public function suspend(ServiceAccount $account): ServiceAccount
    {
        return DB::transaction(function () use ($account) {
            $account = $this->access->suspend($account);
            $this->radius->setStatus($account, 'suspended');
            return $account->fresh();
        });
    }
}
