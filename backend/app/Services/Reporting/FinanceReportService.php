<?php
namespace App\Services\Reporting;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class FinanceReportService
{
    public function summary(): array
    {
        return [
            'revenue'=>round((float)Payment::where('status','completed')->sum('amount'),2),
            'outstanding'=>round((float)Invoice::whereIn('status',['issued','partial','overdue'])->sum('amount_due'),2),
            'overdue'=>round((float)Invoice::where('status','overdue')->sum('amount_due'),2),
            'customers'=>Customer::count(),
            'active_subscriptions'=>Subscription::where('status','active')->count(),
            'suspended_subscriptions'=>Subscription::where('status','suspended')->count(),
        ];
    }

    public function monthlyRevenue(int $months=12): array
    {
        $start=now()->startOfMonth()->subMonths($months-1);
        return Payment::query()->where('status','completed')->where('paid_at','>=',$start)->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as month, SUM(amount) as total")->groupBy('month')->orderBy('month')->get()->map(fn($r)=>['month'=>$r->month,'total'=>(float)$r->total])->all();
    }
}
