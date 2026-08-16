<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Billing\PaymentService;
use App\Services\Payments\MpesaPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MpesaController extends Controller
{
    public function stkPush(Request $request, MpesaPaymentService $mpesa): JsonResponse
    {
        $data=$request->validate(['customer_id'=>['required','integer','exists:customers,id'],'invoice_id'=>['required','integer','exists:invoices,id'],'phone'=>['required','string','max:30'],'amount'=>['required','numeric','gt:0']]);
        $invoice=Invoice::findOrFail($data['invoice_id']);
        abort_unless($invoice->customer_id===(int)$data['customer_id'],422,'Invoice does not belong to customer.');
        abort_if($invoice->status==='paid'||(float)$invoice->amount_due<=0,422,'Invoice is already paid.');
        abort_if((float)$data['amount']>(float)$invoice->amount_due,422,'Amount exceeds invoice balance.');
        $payment=Payment::create(['payment_number'=>'PAY-'.now()->format('Ym').'-'.Str::upper(Str::random(7)),'customer_id'=>$data['customer_id'],'method'=>'mpesa','amount'=>$data['amount'],'currency'=>'KES','external_reference'=>'STK-'.Str::upper(Str::random(12)),'status'=>'pending','metadata'=>['invoice_id'=>$invoice->id]]);
        $response=$mpesa->initiateStkPush($data['phone'],(float)$data['amount'],$invoice->invoice_number,$payment->payment_number);
        if(!empty($response['CheckoutRequestID']))$payment->update(['external_reference'=>$response['CheckoutRequestID'],'metadata'=>array_merge((array)$payment->metadata,['stk_response'=>$response])]);
        return response()->json(['data'=>$payment->fresh(),'mpesa'=>$response],202);
    }

    public function callback(Request $request, MpesaPaymentService $mpesa, PaymentService $payments): JsonResponse
    {
        $payment=$mpesa->recordCallback($request->all());
        if($payment?->status==='completed'){
            $invoiceId=data_get($payment->metadata,'invoice_id');
            $invoice=$invoiceId?Invoice::find($invoiceId):null;
            if($invoice && $invoice->customer_id===$payment->customer_id && $invoice->status!=='paid') $payments->allocate($payment,$invoice,min((float)$payment->amount,(float)$invoice->amount_due));
        }
        return response()->json(['ResultCode'=>0,'ResultDesc'=>'Accepted']);
    }
}
