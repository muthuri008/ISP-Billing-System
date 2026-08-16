<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Services\Network\RouterHealthService;
use App\Services\Network\SessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NetworkController extends Controller
{
    public function routers(): JsonResponse
    {
        return response()->json(Router::query()->orderBy('name')->paginate(25));
    }

    public function health(Router $router, RouterHealthService $health): JsonResponse
    {
        $online = $health->check($router);
        return response()->json(['online' => $online, 'router' => $router->fresh()]);
    }

    public function sessions(Router $router, SessionService $sessions): JsonResponse
    {
        return response()->json(['data' => $sessions->list($router)->values()]);
    }

    public function disconnect(Request $request, Router $router, SessionService $sessions): JsonResponse
    {
        $validated = $request->validate(['session_id' => ['required', 'string', 'max:255']]);
        $ok = $sessions->disconnect($router, $validated['session_id']);
        return response()->json(['success' => $ok], $ok ? 200 : 422);
    }
}
