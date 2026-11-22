<?php

namespace App\Services\Network;

use App\Models\Router;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RouterClient
{
    public function __construct(private readonly Router $router) {}

    public function health(): bool
    {
        try {
            $response = $this->request()->get($this->url('/rest/system/resource'));
            if ($response->successful()) {
                $this->router->update(['last_seen_at' => now(), 'status' => 'active']);
                return true;
            }
        } catch (\Throwable) {
            // Health checks report the router as unavailable without leaking credentials.
        }

        $this->router->update(['status' => 'inactive']);
        return false;
    }

    public function createPppSecret(string $username, string $password, string $profile, bool $disabled): void
    {
        $payload = [
            'name' => $username,
            'password' => $password,
            'service' => 'pppoe',
            'profile' => $profile,
            'disabled' => $disabled ? 'yes' : 'no',
        ];

        // Provisioning is intentionally idempotent: update an existing PPP
        // secret rather than failing with a duplicate-name error on retries.
        $existing = collect($this->send('GET', '/rest/ppp/secret'))->first(
            fn (array $item): bool => ($item['name'] ?? null) === $username
        );
        $resourceId = $existing['.id'] ?? null;

        if (is_string($resourceId) && $resourceId !== '') {
            $this->send('PATCH', '/rest/ppp/secret/'.rawurlencode($resourceId), $payload);
            return;
        }

        $this->send('PUT', '/rest/ppp/secret', $payload);
    }

    public function setUserDisabled(string $username, bool $disabled): void
    {
        // RouterOS REST PATCH requires the record's internal .id, not the
        // PPP secret's display name. Resolve the ID before changing the record.
        $secrets = $this->send('GET', '/rest/ppp/secret');
        $secret = collect($secrets)->first(
            fn (array $item): bool => ($item['name'] ?? null) === $username
        );

        $resourceId = $secret['.id'] ?? null;
        if (! is_string($resourceId) || $resourceId === '') {
            throw new RuntimeException('The PPP secret could not be found on the router.');
        }

        $this->send('PATCH', '/rest/ppp/secret/'.rawurlencode($resourceId), [
            'disabled' => $disabled ? 'yes' : 'no',
        ]);
    }

    public function identity(): array
    {
        return $this->send('GET', '/rest/system/identity');
    }

    public function activeUsers(): array
    {
        return $this->send('GET', '/rest/ppp/active');
    }

    public function disconnectUser(string $id): bool
    {
        $response = $this->request()->delete($this->url('/rest/ppp/active/'.rawurlencode($id)));
        return $response->successful();
    }

    private function send(string $method, string $path, array $payload = []): array
    {
        $request = $this->request();
        $response = match ($method) {
            'PUT' => $request->put($this->url($path), $payload),
            'PATCH' => $request->patch($this->url($path), $payload),
            default => $request->get($this->url($path)),
        };

        if (! $response->successful()) {
            throw new RuntimeException('MikroTik REST request failed with HTTP '.$response->status().'.');
        }

        return $response->json() ?? [];
    }

    private function request()
    {
        return Http::timeout(10)
            ->withBasicAuth($this->router->api_username, $this->router->api_password);
    }

    private function url(string $path): string
    {
        $scheme = $this->router->rest_scheme ?: 'https';
        $port = $this->router->rest_port ?: 443;

        if (app()->environment('production') && $scheme !== 'https') {
            throw new RuntimeException('Production MikroTik connections must use HTTPS.');
        }

        return $scheme.'://'.$this->router->hostname.':'.$port.'/'.ltrim($path, '/');
    }
}
