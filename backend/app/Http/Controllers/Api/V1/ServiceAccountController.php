<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ServiceAccount;
use App\Services\Network\ServiceAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceAccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query=ServiceAccount::with(['customer:id,customer_number,first_name,last_name','router:id,name']);
        if($request->filled('status')) $query->where('status',$request->string('status'));
        return response()->json($query->latest()->paginate(min($request->integer('per_page',25),100)));
    }

    public function store(Request $request): JsonResponse
    {
        $data=$request->validate([
            'customer_id'=>['required','integer','exists:customers,id'], 'subscription_id'=>['nullable','integer','exists:subscriptions,id'],
            'router_id'=>['nullable','integer','exists:routers,id'], 'username'=>['required','string','max:120','unique:service_accounts,username'],
            'password'=>['required','string','min:10','max:128'], 'access_type'=>['required','in:pppoe,hotspot,static'],
            'mac_address'=>['nullable','string','max:32'], 'ip_address'=>['nullable','ip'],
        ]);
        $data['password_hash']=$data['password']; unset($data['password']);
        return response()->json(['data'=>ServiceAccount::create($data)->load(['customer','router'])],201);
    }

    public function suspend(ServiceAccount $serviceAccount, ServiceAccessService $access): JsonResponse { return response()->json(['data'=>$access->suspend($serviceAccount)]); }
    public function activate(ServiceAccount $serviceAccount, ServiceAccessService $access): JsonResponse { return response()->json(['data'=>$access->activate($serviceAccount)]); }
}
