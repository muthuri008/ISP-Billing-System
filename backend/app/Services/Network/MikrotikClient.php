<?php
namespace App\Services\Network;

use App\Models\Router;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MikrotikClient
{
    public function __construct(private readonly Router $router) {}

    private function url(string $path): string { return rtrim($this->router->api_url,'/').'/'.ltrim($path,'/'); }

    private function request()
    {
        return Http::timeout(10)->withBasicAuth($this->router->api_username, $this->router->api_password);
    }

    public function testConnection(): bool
    {
        $response=$this->request()->get($this->url('/rest/system/identity'));
        if(!$response->successful()) throw new RuntimeException('MikroTik connection failed.');
        return true;
    }

    public function identity(): array
    {
        $response=$this->request()->get($this->url('/rest/system/identity'));
        if(!$response->successful()) throw new RuntimeException('Unable to read router identity.');
        return $response->json();
    }

    public function activeUsers(): array
    {
        $response=$this->request()->get($this->url('/rest/ppp/active'));
        if(!$response->successful()) throw new RuntimeException('Unable to read active PPP sessions.');
        return $response->json() ?? [];
    }

    public function disconnectUser(string $id): bool
    {
        $response=$this->request()->delete($this->url('/rest/ppp/active/'.rawurlencode($id)));
        return $response->successful();
    }
}
