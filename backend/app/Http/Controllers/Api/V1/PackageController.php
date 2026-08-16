<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Package\StorePackageRequest;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Package::query();
        if ($request->filled('active')) $query->where('is_active', $request->boolean('active'));
        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(fn ($q) => $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
        }
        return response()->json($query->latest()->paginate(min($request->integer('per_page', 25), 100)));
    }

    public function store(StorePackageRequest $request): JsonResponse
    {
        $package = Package::create($request->validated());
        return response()->json(['data' => $package], 201);
    }

    public function show(Package $package): JsonResponse
    {
        return response()->json(['data' => $package]);
    }

    public function update(StorePackageRequest $request, Package $package): JsonResponse
    {
        $data = $request->validated();
        if ($data['code'] !== $package->code) {
            $request->validate(['code' => 'unique:packages,code,'.$package->id]);
        }
        $package->update($data);
        return response()->json(['data' => $package->fresh()]);
    }

    public function destroy(Package $package): JsonResponse
    {
        $package->update(['is_active' => false]);
        $package->delete();
        return response()->json(['message' => 'Package archived.']);
    }
}
