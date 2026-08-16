<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Portal\CustomerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerPortalController extends Controller
{
    public function dashboard(Request $request, CustomerDashboardService $dashboard): JsonResponse
    {
        $customerId=$request->user()->customer_id;
        abort_unless($customerId,403,'Authenticated account is not linked to a customer.');
        $customer=Customer::findOrFail($customerId);
        return response()->json(['data'=>$dashboard->summary($customer)]);
    }
}
