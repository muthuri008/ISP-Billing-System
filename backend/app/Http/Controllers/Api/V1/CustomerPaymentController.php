<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Payments\MpesaPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerPaymentController extends Controller
{
    public function payInvoice(Request $request, Invoice $invoice, MpesaPaymentService $mpesa): JsonResponse
    {
        $customer=$request->user()->customer;
        abort_unless($customer && $invoice->customer_id === $customer->id,404);
        abort_if((float)$invoice->amount_due<=0 || $invoice->status==='paid',422,'Invoice has no outstanding balance.');

        $data=$request->validate(['phone'=>['required','string','max:20']]);
        $response=$mpesa->initiateStkPush($data['phone'],(float)$invoice->amount_due,$invoice->invoice_number,'ISP invoice payment');
        return response()->json(['data'=>['invoice_id'=>$invoice->id,'amount'=>(float)$invoice->amount_due,'stk'=>$response]]);
    }
}
