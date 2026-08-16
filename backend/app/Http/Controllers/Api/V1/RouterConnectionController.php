<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Services\Network\RouterHealthService;
use Illuminate\Http\JsonResponse;

class RouterConnectionController extends Controller
{
    public function test(Router $router, RouterHealthService $health): JsonResponse
    {
        $result=$health->check($router);
        return response()->json(['data'=>['router_id'=>$router->id,'online'=>$result['online'],'identity'=>$result['identity']]],$result['online']?200:503);
    }
}
