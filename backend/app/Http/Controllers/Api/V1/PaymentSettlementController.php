<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Billing\PaymentSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentSettlementController extends Controller
{
    public function settle(Request $request, Payment $payment, PaymentSettlementService $settlement): JsonResponse
    {
        $request->validate(['invoice_id' => ['nullable','integer','exists:invoices,id']]);
        $invoice = $request->filled('invoice_id') ? Invoice::findOrFail($request->integer('invoice_id')) : null;
        abort_unless($invoice === null || $invoice->customer_id === $payment->customer_id, 422, 'Invoice does not belong to the payment customer.');
        $allocated = $settlement->settle($payment, $invoice);
        return response()->json(['data'=>['payment_id'=>$payment->id,'allocated'=>$allocated,'payment'=>$payment->fresh(),'allocations'=>$payment->allocations()->with('invoice')->get()]]);
    }
}
