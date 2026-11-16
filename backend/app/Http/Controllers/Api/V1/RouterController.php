<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Router;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RouterController extends Controller
{
    private function rules(bool $creating): array
    {
        $rules = [
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:120'],
            'hostname' => [$creating ? 'required' : 'sometimes', 'string', 'max:255', 'regex:/^[A-Za-z0-9.-]+$/'],
            'rest_scheme' => [$creating ? 'required' : 'sometimes', Rule::in(app()->environment('production') ? ['https'] : ['https', 'http'])],
            'rest_port' => [$creating ? 'required' : 'sometimes', 'integer', 'between:1,65535'],
            'api_port' => ['sometimes', 'integer', 'between:1,65535'],
            'api_username' => [$creating ? 'required' : 'sometimes', 'string', 'max:120'],
            'api_password' => [$creating ? 'required' : 'sometimes', 'nullable', 'string', 'max:500'],
            'radius_secret' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'maintenance'])],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];

        return $rules;
    }

    public function index(): JsonResponse
    {
        return response()->json(['data' => Router::query()->latest()->paginate(25)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules(true));
        $router = Router::create($data);

        return response()->json([
            'data' => $router->fresh(),
            'message' => 'Router configuration saved. Run a health check to verify connectivity.',
        ], 201);
    }

    public function show(Router $router): JsonResponse
    {
        return response()->json(['data' => $router]);
    }

    public function update(Request $request, Router $router): JsonResponse
    {
        $data = $request->validate($this->rules(false));
        if (($data['api_password'] ?? null) === null) {
            unset($data['api_password']);
        }
        if (($data['radius_secret'] ?? null) === null) {
            unset($data['radius_secret']);
        }

        $router->update($data);

        return response()->json(['data' => $router->fresh()]);
    }
}
