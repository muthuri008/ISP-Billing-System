<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use App\\Services\\Payments\\MpesaPaymentService;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;

class MpesaCallbackController extends Controller
{
    public function __invoke(Request $request, MpesaPaymentService $payments): JsonResponse
    {
        // Daraja sends Body.stkCallback. Never accept customer IDs, amounts or
        // transaction references supplied independently by the callback caller.
        $checkoutRequestId = data_get($request->all(), 'Body.stkCallback.CheckoutRequestID');
        $resultCode = data_get($request->all(), 'Body.stkCallback.ResultCode');

        if (! is_string($checkoutRequestId) || $checkoutRequestId === '' || ! is_numeric($resultCode)) {
            return response()->json([
                'ResultCode' => 1,
                'ResultDesc' => 'Invalid callback payload.',
            ], 422);
        }

        $payment = $payments->recordCallback($request->all());

        if (! $payment) {
            return response()->json([
                'ResultCode' => 1,
                'ResultDesc' => 'No matching pending STK request was found.',
            ], 404);
        }

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Callback accepted.',
        ]);
    }
}
