<?php
namespace App\Services\Network;

use App\Models\Router;
use Illuminate\Support\Facades\Log;

class RouterHealthService
{
    public function check(Router $router): array
    {
        try {
            $client=new MikrotikClient($router);
            $identity=$client->identity();
            $router->forceFill(['status'=>'online','last_seen_at'=>now(),'metadata'=>array_merge((array)$router->metadata,['identity'=>$identity])])->save();
            return ['online'=>true,'identity'=>$identity];
        } catch (\Throwable $e) {
            Log::warning('Router health check failed',['router_id'=>$router->id,'error'=>$e->getMessage()]);
            $router->forceFill(['status'=>'offline'])->save();
            return ['online'=>false,'identity'=>null];
        }
    }
}
