<?php

namespace Basis\Configuration;

use Basis\Container;
use Basis\Dispatcher;
use Basis\Nats\Client;
use Basis\Nats\Configuration;

class Nats
{
    public function init(Container $container)
    {
        $container->share(Configuration::class, function () use ($container) {
            return new Configuration([
                'host' => $container->get(Dispatcher::class)
                    ->dispatch('resolve.address', ['name' => 'nats-service'])
                    ->host,
            ]);
        });

        $container->share(Client::class, function () use ($container) {
            return new Client($container->get(Configuration::class));
        });
    }
}
