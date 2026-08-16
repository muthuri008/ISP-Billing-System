<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\ServiceAccount;
use App\Services\Network\RadiusPolicyService;
use Illuminate\Http\JsonResponse;

class RadiusController extends Controller
{
    public function provision(ServiceAccount $serviceAccount, RadiusPolicyService $radius): JsonResponse
    {
        $user = $radius->provision($serviceAccount->load('subscription.package'));
        return response()->json(['data' => $user->load('serviceAccount')], 201);
    }

    public function syncPackage(Package $package, RadiusPolicyService $radius): JsonResponse
    {
        return response()->json(['data' => $radius->syncProfile($package)]);
    }
}
