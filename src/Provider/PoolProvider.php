<?php

namespace Basis\Provider;

use Basis\Service;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\Request\DeleteRequest;
use Tarantool\Client\Request\InsertRequest;
use Tarantool\Client\Request\ReplaceRequest;
use Tarantool\Client\Request\UpdateRequest;
use Tarantool\Mapper\Client;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Plugin;
use Tarantool\Mapper\Plugin\Spy;
use Tarantool\Mapper\Plugin\Temporal;
use Tarantool\Mapper\Pool;
use Tarantool\Mapper\Schema;

class PoolProvider extends AbstractServiceProvider
{
    protected $provides = [
        Pool::class,
    ];

    public function register()
    {
        $this->getContainer()->share(Pool::class, function () {
            $container = $this->getContainer();

            $pool = new Pool();
            $pool->register('default', $container->get(Mapper::class));

            $service = $container->get(Service::class);
            $pool->register($service->getName(), $container->get(Mapper::class));

            $pool->registerResolver(function ($name) use ($service) {
                if (in_array($name, $service->listServices())) {
                    $connection = new StreamConnection('tcp://'.$name.'-db:3301');
                    $packer = new PurePacker();
                    $client = new Client($connection, $packer);
                    $client->disableRequest(DeleteRequest::class);
                    $client->disableRequest(InsertRequest::class);
                    $client->disableRequest(ReplaceRequest::class);
                    $client->disableRequest(UpdateRequest::class);
                    $mapper = new Mapper($client);
                    $mapper->getPlugin(Temporal::class);
                    $mapper->getPlugin(Spy::class);
                    return $mapper;
                }
            });

            return $pool;
        });
    }
}
