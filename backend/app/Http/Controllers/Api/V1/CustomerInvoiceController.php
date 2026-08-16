<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerInvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customer=$request->user()->customer;
        abort_unless($customer,403,'Authenticated account is not linked to a customer.');
        $invoices=Invoice::where('customer_id',$customer->id)->orderByDesc('due_date')->paginate(15);
        return response()->json($invoices);
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $customer=$request->user()->customer;
        abort_unless($customer && $invoice->customer_id === $customer->id,404);
        return response()->json(['data'=>$invoice->load(['customer','allocations.payment'])]);
    }
}
