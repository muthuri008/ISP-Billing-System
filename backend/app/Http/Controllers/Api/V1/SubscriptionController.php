<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\StoreSubscriptionRequest;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Subscription;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Subscription::with(['customer:id,customer_number,first_name,last_name,phone', 'package:id,code,name,price,billing_cycle']);
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('customer_id')) $query->where('customer_id', $request->integer('customer_id'));
        return response()->json($query->latest()->paginate(min($request->integer('per_page', 25), 100)));
    }

    public function store(StoreSubscriptionRequest $request, SubscriptionService $service): JsonResponse
    {
        $data = $request->validated();
        $subscription = DB::transaction(fn () => $service->create(
            Customer::findOrFail($data['customer_id']),
            Package::findOrFail($data['package_id']),
            $data
        ));
        return response()->json(['data' => $subscription->load(['customer', 'package'])], 201);
    }

    public function show(Subscription $subscription): JsonResponse
    {
        return response()->json(['data' => $subscription->load(['customer', 'package'])]);
    }

    public function activate(Subscription $subscription, SubscriptionService $service): JsonResponse
    {
        return response()->json(['data' => $service->activate($subscription)]);
    }

    public function suspend(Subscription $subscription, SubscriptionService $service): JsonResponse
    {
        return response()->json(['data' => $service->suspend($subscription)]);
    }

    public function cancel(Subscription $subscription, SubscriptionService $service): JsonResponse
    {
        return response()->json(['data' => $service->cancel($subscription)]);
    }
}
