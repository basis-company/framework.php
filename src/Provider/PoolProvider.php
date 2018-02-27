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
                    return $container->get(Mapper::class);
                }

                if (in_array($name, $container->get(Service::class)->listServices())) {
                    $connection = new StreamConnection('tcp://'.$name.'-db:3301');
                    $packer = new PurePacker();
                    $client = new Client($connection, $packer);
                    $client->disableRequest(DeleteRequest::class);
                    $client->disableRequest(InsertRequest::class);
                    $client->disableRequest(ReplaceRequest::class);
                    $client->disableRequest(UpdateRequest::class);
                    $mapper = new Mapper($client);
                    $mapper->getPlugin(Sequence::class);
                    $mapper->getPlugin(Spy::class);
                    $mapper->getPlugin(Temporal::class);
                    $mapper->serviceName = $name;
                    return $mapper;
                }
            });

            return $pool;
        });
    }
}
