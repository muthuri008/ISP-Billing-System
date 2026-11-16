<?php
namespace App\Services\Network;

use App\Models\Router;
use Illuminate\Support\Facades\Log;

class RouterHealthService
{
    public function check(Router $router): array
    {
        try {
            $identity = (new MikrotikClient($router))->identity();
            $router->forceFill([
                'status' => 'active',
                'last_seen_at' => now(),
                'metadata' => array_merge((array) $router->metadata, ['identity' => $identity]),
            ])->save();

            return ['online' => true, 'identity' => $identity];
        } catch (\Throwable $exception) {
            Log::warning('Router health check failed', [
                'router_id' => $router->id,
                'error' => $exception->getMessage(),
            ]);
            $router->forceFill(['status' => 'inactive'])->save();

            return ['online' => false, 'identity' => null];
        }
    }
}
