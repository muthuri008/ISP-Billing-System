<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Billing\PaymentService;
use App\Services\Payments\MpesaPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MpesaController extends Controller
{
    public function stkPush(Request $request, MpesaPaymentService $mpesa): JsonResponse
    {
        $data = $request->validate([
            'customer_id' => ['required','integer','exists:customers,id'],
            'invoice_id' => ['required','integer','exists:invoices,id'],
            'phone' => ['required','string','max:30'],
            'amount' => ['required','numeric','gt:0'],
        ]);
        $invoice = Invoice::findOrFail($data['invoice_id']);
        abort_unless($invoice->customer_id === (int) $data['customer_id'], 422, 'Invoice does not belong to customer.');
        abort_if((float) $data['amount'] > (float) $invoice->amount_due, 422, 'Amount exceeds invoice balance.');

        $reference = 'STK-'.Str::upper(Str::random(12));
        $payment = Payment::create([
            'payment_number' => 'PAY-'.now()->format('Ym').'-'.Str::upper(Str::random(7)),
            'customer_id' => $data['customer_id'], 'method' => 'mpesa', 'amount' => $data['amount'],
            'currency' => 'KES', 'external_reference' => $reference, 'status' => 'pending',
        ]);
        $response = $mpesa->initiateStkPush($data['phone'], (float) $data['amount'], $invoice->invoice_number, 'ISP Internet payment');
        if (! empty($response['CheckoutRequestID'])) $payment->update(['external_reference' => $response['CheckoutRequestID']]);
        return response()->json(['data' => $payment->fresh(), 'mpesa' => $response], 202);
    }

    public function callback(Request $request, MpesaPaymentService $mpesa, PaymentService $payments): JsonResponse
    {
        $payment = DB::transaction(fn () => $mpesa->recordCallback($request->all()));
        if ($payment?->status === 'completed') {
            $invoice = Invoice::where('customer_id', $payment->customer_id)->where('status', '!=', 'paid')->orderBy('due_date')->first();
            if ($invoice) $payments->allocate($payment, $invoice, min((float) $payment->amount, (float) $invoice->amount_due));
        }
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
