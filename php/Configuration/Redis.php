<?php

namespace Basis\Configuration;

use Basis\Container;
use Basis\Toolkit;
use Predis\Client;

class Redis
{
    use Toolkit;

    public function init(Container $container)
    {
        $container->share(Redis::class, function () {
            $host = getenv('REDIS_SERVICE_HOST');
            if (!$host) {
                $host = $this->app->getName() . '-redis';
            }
            $address = $this->dispatch('resolve.address', [ 'name' => $host ]);
            $client = new Client([
                'scheme' => 'tcp',
                'host' => $address->host,
                'port' => 6379,
            ]);
            return $client;
        });
    }
}
