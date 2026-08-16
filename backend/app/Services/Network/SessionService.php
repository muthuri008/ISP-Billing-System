<?php

namespace App\Services\Network;

use App\Models\Router;
use Illuminate\Support\Collection;

class SessionService
{
    public function list(Router $router): Collection
    {
        return collect((new MikrotikClient($router))->activeUsers());
    }

    public function disconnect(Router $router, string $sessionId): bool
    {
        return (new MikrotikClient($router))->disconnectUser($sessionId);
    }
}
