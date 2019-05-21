<?php

namespace Basis\Provider;

use Basis\Service;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Tarantool\Client\Client;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Plugin\Sequence;
use Tarantool\Mapper\Plugin\Spy;
use Tarantool\Mapper\Plugin\Temporal;
use Tarantool\Mapper\Pool;

class PoolProvider extends AbstractServiceProvider
{
    protected $provides = [
        Pool::class,
    ];

    public function register()
    {
        $this->getContainer()->share(Pool::class, function () {
            $pool = new Pool();
            $container = $this->getContainer();
            $pool->registerResolver(function ($name) use ($container) {

                if ($name == 'default' || $name == $container->get(Service::class)->getName()) {
                    $mapper = $container->get(Mapper::class);
                    $mapper->serviceName = $container->get(Service::class)->getName();
                    return $mapper;
                }

                $service = $container->get(Service::class);

                if (in_array($name, $service->listServices())) {
                    $address = $service->getHost($name.'-db')->address;
                    $client = Client::fromDsn('tcp://'.$address.':3301');
                    $mapper = new Mapper($client);
                    $mapper->getPlugin(Sequence::class);
                    $mapper->getPlugin(Spy::class);
                    $mapper->getPlugin(Temporal::class)
                        ->getAggregator()
                        ->setReferenceAggregation(false);

                    $mapper->serviceName = $name;
                    return $mapper;
                }
            });

            return $pool;
        });
    }
}
