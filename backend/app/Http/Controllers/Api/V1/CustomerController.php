<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\Customer\CustomerNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::with('addresses');

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('billing_type')) $query->where('billing_type', $request->string('billing_type'));

        return response()->json($query->latest()->paginate(min($request->integer('per_page', 25), 100)));
    }

    public function store(StoreCustomerRequest $request, CustomerNumberService $numbers): JsonResponse
    {
        $customer = DB::transaction(function () use ($request, $numbers) {
            $data = $request->validated();
            $address = $data['address'] ?? null;
            unset($data['address']);
            $data['customer_number'] = $numbers->generate();
            $data['registered_at'] ??= now()->toDateString();
            $customer = Customer::create($data);
            if ($address) $customer->addresses()->create(array_merge($address, ['is_primary' => true]));
            return $customer->load('addresses');
        });

        return response()->json(['data' => $customer], 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        return response()->json(['data' => $customer->load('addresses')]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer->update($request->validated());
        return response()->json(['data' => $customer->fresh()->load('addresses')]);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->update(['status' => 'disconnected']);
        $customer->delete();
        return response()->json(['message' => 'Customer archived.']);
    }
}
