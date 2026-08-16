<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Services\Billing\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with(['customer:id,customer_number,first_name,last_name','subscription:id,subscription_number']);
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('customer_id')) $query->where('customer_id', $request->integer('customer_id'));
        return response()->json($query->latest()->paginate(min($request->integer('per_page', 25), 100)));
    }

    public function store(Request $request, InvoiceService $service): JsonResponse
    {
        $data = $request->validate([
            'subscription_id' => ['required', 'integer', 'exists:subscriptions,id'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);
        $invoice = DB::transaction(fn () => $service->createForSubscription(Subscription::with('package')->findOrFail($data['subscription_id']), $data['due_date'] ?? null));
        return response()->json(['data' => $invoice->load(['customer','subscription','items'])], 201);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json(['data' => $invoice->load(['customer','subscription.package','items'])]);
    }
}
