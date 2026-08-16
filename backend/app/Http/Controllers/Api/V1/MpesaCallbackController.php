<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Payments\MpesaCallbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MpesaCallbackController extends Controller
{
    public function __invoke(Request $request, MpesaCallbackService $service): JsonResponse
    {
        $data=$request->validate([
            'transaction_reference'=>['required','string','max:120'],
            'amount'=>['required','numeric','gt:0'],
            'customer_id'=>['required','integer','exists:customers,id'],
            'currency'=>['nullable','string','size:3'],
            'external_reference'=>['nullable','string','max:120'],
        ]);
        $result=$service->handle($data);
        return response()->json(['data'=>$result],$result['duplicate'] ? 200 : 201);
    }
}
