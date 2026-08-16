<?php
namespace App\Services\Network;

use App\Models\Router;

class RouterClientFactory
{
    public function for(Router $router): RouterClient
    {
        return new RouterClient($router);
    }
}
