<?php

namespace Tests\Feature;

use App\Models\Router;
use App\Services\Network\RouterClient;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RouterClientTest extends TestCase
{
    public function test_ppp_secret_provisioning_and_suspension_use_router_resource_id(): void
    {
        Http::fakeSequence()
            ->push([['.id' => '*1', 'name' => 'stellar-test']], 200)
            ->push([], 200)
            ->push([['.id' => '*1', 'name' => 'stellar-test']], 200)
            ->push([], 200);

        $router = new Router([
            'hostname' => '192.0.2.10',
            'rest_scheme' => 'https',
            'rest_port' => 443,
            'api_username' => 'test-admin',
            'api_password' => 'test-secret',
        ]);

        $client = new RouterClient($router);
        $client->createPppSecret('stellar-test', 'pppoe-password', 'HOME-100', false);
        $client->setUserDisabled('stellar-test', true);

        Http::assertSent(fn (ClientRequest $request): bool =>
            $request->method() === 'PATCH'
            && str_contains($request->url(), '/rest/ppp/secret/%2A1')
        );
        Http::assertSentCount(4);
    }
}
