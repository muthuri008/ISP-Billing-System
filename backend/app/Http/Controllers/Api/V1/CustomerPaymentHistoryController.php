<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerPaymentHistoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customer=$request->user()->customer;
        abort_unless($customer,403,'Authenticated account is not linked to a customer.');
        $payments=Payment::where('customer_id',$customer->id)
            ->orderByDesc('created_at')
            ->paginate(15);
        return response()->json($payments);
    }
}
