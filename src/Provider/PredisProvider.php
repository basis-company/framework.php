<?php

namespace Basis\Provider;

use Basis\Service;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Predis\Client;

class PredisProvider extends AbstractServiceProvider
{
    protected $provides = [
        Client::class,
    ];

    public function register()
    {
        $this->getContainer()->share(Client::class, function () {
            $service = $this->getContainer()->get(Service::class);
            $host = getenv('REDIS_SERVICE_HOST');
            if (!$host) {
                $redisService = $service->getName().'-redis';
                $host = $service->getHost($redisService)->address;
            }
            $client = new Client([
                'scheme' => 'tcp',
                'host' => $host,
                'port' => 6379,
            ]);
            return $client;
        });
    }
}
