<?php

namespace App\Services\Network;

use App\Models\Router;
use Illuminate\Support\Collection;

class SessionService
{
    public function list(Router $router): Collection
    {
        return collect((new MikrotikClient($router))->activeUsers())
            ->map(function (array $session): array {
                return [
                    'session_id' => (string) ($session['.id'] ?? $session['session-id'] ?? $session['session_id'] ?? ''),
                    'username' => $session['name'] ?? $session['username'] ?? null,
                    'address' => $session['address'] ?? $session['remote-address'] ?? null,
                    'ip_address' => $session['address'] ?? $session['remote-address'] ?? null,
                ];
            })
            ->filter(fn (array $session): bool => $session['session_id'] !== '')
            ->values();
    }

    public function disconnect(Router $router, string $sessionId): bool
    {
        return (new MikrotikClient($router))->disconnectUser($sessionId);
    }
}
