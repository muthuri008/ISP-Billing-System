<?php
namespace App\Services\Network;

use App\Models\Package;
use App\Models\RadiusProfile;
use App\Models\RadiusUser;
use App\Models\ServiceAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RadiusPolicyService
{
    public function syncProfile(Package $package): RadiusProfile
    {
        return RadiusProfile::updateOrCreate(
            ['package_id' => $package->id],
            ['name' => 'PKG-'.$package->id, 'download_speed' => $package->download_speed, 'upload_speed' => $package->upload_speed, 'attributes' => ['Mikrotik-Rate-Limit' => $package->download_speed.'/'.$package->upload_speed]]
        );
    }

    public function provision(ServiceAccount $account): RadiusUser
    {
        if (! $account->subscription_id) throw ValidationException::withMessages(['subscription' => ['A service account requires a subscription for RADIUS provisioning.']]);
        $package = $account->subscription?->package;
        if (! $package) throw ValidationException::withMessages(['package' => ['The subscription package could not be found.']]);

        return DB::transaction(function () use ($account, $package) {
            $this->syncProfile($package);
            return RadiusUser::updateOrCreate(
                ['service_account_id' => $account->id],
                ['username' => $account->username, 'auth_type' => 'password', 'password' => $account->password_hash, 'status' => $account->status]
            );
        });
    }

    public function setStatus(ServiceAccount $account, string $status): RadiusUser
    {
        return RadiusUser::where('service_account_id', $account->id)->updateOrCreate(
            ['service_account_id' => $account->id],
            ['username' => $account->username, 'status' => $status]
        );
    }
}
