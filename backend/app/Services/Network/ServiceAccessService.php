<?php
namespace App\Services\Network;

use App\Models\ServiceAccount;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ServiceAccessService
{
    public function __construct(private readonly RouterClientFactory $clients) {}

    public function suspend(ServiceAccount $account): ServiceAccount
    {
        if ($account->status === 'disabled') return $account;
        if ($account->router && $account->access_type === 'pppoe') {
            try { $this->clients->for($account->router)->setUserDisabled($account->username, true); }
            catch (\Throwable $e) { throw new RuntimeException('Unable to suspend the MikroTik account: '.$e->getMessage(),0,$e); }
        }
        $account->update(['status' => 'suspended']);
        return $account->fresh();
    }

    public function activate(ServiceAccount $account): ServiceAccount
    {
        if ($account->status === 'disabled') throw ValidationException::withMessages(['status' => ['Disabled service accounts must be re-provisioned before activation.']]);
        if ($account->router && $account->access_type === 'pppoe') {
            try { $this->clients->for($account->router)->setUserDisabled($account->username, false); }
            catch (\Throwable $e) { throw new RuntimeException('Unable to activate the MikroTik account: '.$e->getMessage(),0,$e); }
        }
        $account->update(['status' => 'active']);
        return $account->fresh();
    }
}
