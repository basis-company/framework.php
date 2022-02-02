<?php

namespace Basis\Configuration;

use Basis\Container;
use Basis\Dispatcher;
use Basis\Nats\Client;
use Basis\Nats\Configuration;
use Psr\Log\LoggerInterface;

class Nats
{
    public function init(Container $container)
    {
        $container->share(Configuration::class, function () use ($container) {
            return new Configuration([
                'host' => getenv('NATS_HOST') ?: $container->get(Dispatcher::class)
                    ->dispatch('resolve.address', ['name' => 'nats-service'])
                    ->host,
                'timeout' => 0.5,
            ]);
        });

        $container->share(Client::class, function () use ($container) {
            $client = new Client($container->get(Configuration::class));

            if (getenv('NATS_CLIENT_LOG')) {
                $client->setLogger($container->get(LoggerInterface::class));
            }

            $delay = floatval(getenv('NATS_CLIENT_TIMEOUT') ?: 0.1);
            $client->setDelay($delay);

            return $client;
        });
    }
}
