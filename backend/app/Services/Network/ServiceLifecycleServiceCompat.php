<?php

namespace App\Services\Network;

use App\Models\ServiceAccount;

class ServiceLifecycleServiceCompat
{
    public function __construct(private readonly ServiceLifecycleService $lifecycle) {}

    public function activate(ServiceAccount $account): ServiceAccount
    {
        return $this->lifecycle->restore($account);
    }
}
