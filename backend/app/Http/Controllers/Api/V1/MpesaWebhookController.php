<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Payments\MpesaPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MpesaWebhookController extends Controller
{
    public function __invoke(Request $request, MpesaPaymentService $mpesa): JsonResponse
    {
        $payment = $mpesa->recordCallback($request->all());
        return response()->json(['ResultCode'=>0,'ResultDesc'=>'Accepted','payment_id'=>$payment?->id]);
    }
}
