<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\NetworkSession;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class NetworkSessionController extends Controller
{
    public function index(Request $request)
    {
        $sessions=NetworkSession::with(['serviceAccount.customer','router'])->latest('started_at')->paginate(min((int)$request->integer('per_page',25),100));
        return response()->json($sessions);
    }
    public function store(Request $request)
    {
        $data=$request->validate(['service_account_id'=>['nullable','integer','exists:service_accounts,id'],'router_id'=>['nullable','integer','exists:routers,id'],'session_id'=>['required','string','max:150'],'started_at'=>['required','date'],'ip_address'=>['nullable','ip'],'nas_address'=>['nullable','ip'],'input_octets'=>['sometimes','integer','min:0'],'output_octets'=>['sometimes','integer','min:0'],'status'=>['sometimes',Rule::in(['online','closed'])]]);
        $session=NetworkSession::updateOrCreate(['session_id'=>$data['session_id']],$data);return response()->json($session->load(['serviceAccount.customer','router']),201);
    }
    public function update(Request $request, NetworkSession $session)
    {
        $data=$request->validate(['ended_at'=>['nullable','date','after_or_equal:started_at'],'input_octets'=>['sometimes','integer','min:0'],'output_octets'=>['sometimes','integer','min:0'],'status'=>['sometimes',Rule::in(['online','closed'])]]);
        if(isset($data['ended_at'])){$data['status']='closed';$data['duration_seconds']=max(0,$session->started_at->diffInSeconds($data['ended_at']));} $session->update($data);return response()->json($session->fresh(['serviceAccount.customer','router']));
    }
}
