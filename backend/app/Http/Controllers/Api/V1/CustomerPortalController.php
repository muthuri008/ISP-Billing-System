<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Portal\CustomerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerPortalController extends Controller
{
    public function dashboard(Request $request, CustomerDashboardService $dashboard): JsonResponse
    {
        $customer=$request->user()->customer;
        abort_unless($customer,403,'Authenticated account is not linked to a customer.');
        return response()->json(['data'=>$dashboard->summary($customer)]);
    }
}
