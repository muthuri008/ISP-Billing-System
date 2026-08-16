<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Services\Network\RouterClientFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RouterController extends Controller
{
    public function index(): JsonResponse { return response()->json(['data' => Router::latest()->paginate(25)]); }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'=>['required','string','max:120'], 'hostname'=>['required','string','max:255'],
            'api_port'=>['sometimes','integer','between:1,65535'], 'radius_secret'=>['nullable','string','max:255'],
            'api_username'=>['required','string','max:120'], 'api_password'=>['required','string','max:500'],
            'status'=>['sometimes','in:active,inactive,maintenance'], 'metadata'=>['nullable','array'],
        ]);
        return response()->json(['data'=>Router::create($data)],201);
    }

    public function show(Router $router): JsonResponse { return response()->json(['data'=>$router]); }

    public function health(Router $router, RouterClientFactory $clients): JsonResponse
    {
        return response()->json(['data'=>['router_id'=>$router->id,'reachable'=>$clients->for($router)->health(),'status'=>$router->fresh()->status]]);
    }
}
