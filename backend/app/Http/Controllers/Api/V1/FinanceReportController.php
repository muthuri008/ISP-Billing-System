<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Reporting\FinanceReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceReportController extends Controller
{
    public function summary(FinanceReportService $reports): JsonResponse
    {
        return response()->json(['data'=>$reports->summary()]);
    }

    public function revenue(Request $request, FinanceReportService $reports): JsonResponse
    {
        $months=max(1,min((int)$request->integer('months',12),36));
        return response()->json(['data'=>$reports->monthlyRevenue($months)]);
    }
}
