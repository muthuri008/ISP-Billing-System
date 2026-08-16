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
            $response = Http::timeout(5)->withBasicAuth($this->router->api_username, $this->router->api_password)->get('http://'.$this->router->hostname.':'.$this->router->api_port.'/rest/system/resource');
            if ($response->successful()) { $this->router->update(['last_seen_at'=>now(),'status'=>'active']); return true; }
        } catch (\Throwable) {}
        $this->router->update(['status'=>'inactive']); return false;
    }

    public function createPppSecret(string $username, string $password, string $profile, bool $disabled): void
    {
        $this->request('PUT', '/rest/ppp/secret', ['name'=>$username,'password'=>$password,'service'=>'pppoe','profile'=>$profile,'disabled'=>$disabled?'yes':'no']);
    }

    public function setUserDisabled(string $username, bool $disabled): void
    { $this->request('PATCH', '/rest/ppp/secret/'.rawurlencode($username), ['disabled'=>$disabled?'yes':'no']); }

    private function request(string $method, string $path, array $payload = []): array
    {
        $url='http://'.$this->router->hostname.':'.$this->router->api_port.$path;
        $request=Http::timeout(10)->withBasicAuth($this->router->api_username,$this->router->api_password);
        $response=match($method){'PUT'=>$request->put($url,$payload),'PATCH'=>$request->patch($url,$payload),default=>$request->get($url)};
        if(!$response->successful()) throw new RuntimeException('Router API request failed: '.$response->body());
        return $response->json()??[];
    }
}
