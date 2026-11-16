<?php

namespace App\Services\Network;

use App\Models\Router;

class MikrotikClient
{
    private readonly RouterClient $client;

    public function __construct(Router $router)
    {
        $this->client = new RouterClient($router);
    }

    public function testConnection(): bool
    {
        $this->client->identity();
        return true;
    }

    public function identity(): array
    {
        return $this->client->identity();
    }

    public function activeUsers(): array
    {
        return $this->client->activeUsers();
    }

    public function disconnectUser(string $id): bool
    {
        return $this->client->disconnectUser($id);
    }
}
