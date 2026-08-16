<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Billing\PaymentService;
use App\Services\Billing\PaymentServiceV2;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query=Payment::with('customer:id,customer_number,first_name,last_name');
        if($request->filled('status'))$query->where('status',$request->string('status'));
        if($request->filled('method'))$query->where('method',$request->string('method'));
        return response()->json($query->latest()->paginate(min($request->integer('per_page',25),100)));
    }

    public function store(Request $request, PaymentService $service): JsonResponse
    {
        $data=$request->validate(['customer_id'=>['required','integer','exists:customers,id'],'method'=>['required','in:mpesa,bank,cash,card,manual'],'amount'=>['required','numeric','gt:0'],'currency'=>['sometimes','string','size:3'],'external_reference'=>['nullable','string','max:120'],'transaction_reference'=>['nullable','string','max:120','unique:payments,transaction_reference'],'notes'=>['nullable','string','max:5000'],'metadata'=>['nullable','array']]);
        return response()->json(['data'=>$service->record($data)->load('customer')],201);
    }

    public function allocate(Request $request, Payment $payment, PaymentServiceV2 $service): JsonResponse
    {
        $data=$request->validate(['invoice_id'=>['required','integer','exists:invoices,id'],'amount'=>['required','numeric','gt:0']]);
        $result=$service->allocate($payment,Invoice::findOrFail($data['invoice_id']),(float)$data['amount']);
        return response()->json(['data'=>$result->load('allocations.invoice')]);
    }
}
