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
        $customer = $request->user()->customer;
        abort_unless($customer && $invoice->customer_id === $customer->id, 404);

        abort_unless(
            in_array($invoice->status, ['issued', 'overdue', 'partially_paid'], true),
            422,
            'This invoice is not payable.'
        );
        abort_if((float) $invoice->amount_due <= 0, 422, 'Invoice has no outstanding balance.');
        abort_unless(strtoupper((string) $invoice->currency) === 'KES', 422, 'M-Pesa payments are only supported for KES invoices.');

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $result = $mpesa->initiateInvoiceStkPush($invoice, $data['phone']);

        return response()->json([
            'data' => [
                'invoice_id' => $invoice->id,
                'amount' => (float) $invoice->amount_due,
            ] + $result,
        ], 202);
    }
}
