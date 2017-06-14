<?php

namespace Basis\Provider;

use Basis\Config;
use Basis\Filesystem;
use Basis\Service;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Tarantool\Client\Client as TarantoolClient;
use Tarantool\Client\Connection\Connection;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Packer\Packer;
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\Request\DeleteRequest;
use Tarantool\Client\Request\InsertRequest;
use Tarantool\Client\Request\ReplaceRequest;
use Tarantool\Client\Request\UpdateRequest;
use Tarantool\Mapper\Bootstrap;
use Tarantool\Mapper\Client;
use Tarantool\Mapper\Entity;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Plugin\Annotation;
use Tarantool\Mapper\Plugin\NestedSet;
use Tarantool\Mapper\Plugin\Sequence;
use Tarantool\Mapper\Plugin\Spy;
use Tarantool\Mapper\Plugin;
use Tarantool\Mapper\Pool;
use Tarantool\Mapper\Schema;

class TarantoolProvider extends AbstractServiceProvider
{
    protected $provides = [
        Bootstrap::class,
        Client::class,
        Connection::class,
        Mapper::class,
        Packer::class,
        Pool::class,
        Schema::class,
        Spy::class,
        StreamConnection::class,
        TarantoolClient::class,
    ];

    public function register()
    {
        $this->container->share(Bootstrap::class, function () {
            return $this->container->get(Mapper::class)->getBootstrap();
        });

        $this->getContainer()->share(Pool::class, function () {
            $mapper = $this->getContainer()->get(Mapper::class);

            $pool = new Pool();
            $pool->register('default', $mapper);

            $service = $this->getContainer()->get(Service::class);

            $local = $service->getName();
            $pool->register($local, $mapper);

            foreach ($service->listServices() as $remote) {
                if ($remote != $local) {
                    $pool->register($remote, function () use ($remote) {
                        $connection = new StreamConnection('tcp://'.$remote.'-db:3301');
                        $packer = new PurePacker();
                        $client = new Client($connection, $packer);
                        $client->disableRequest(DeleteRequest::class);
                        $client->disableRequest(InsertRequest::class);
                        $client->disableRequest(ReplaceRequest::class);
                        $client->disableRequest(UpdateRequest::class);
                        return new Mapper($client);
                    });
                }
            }

            return $pool;
        });

        $this->getContainer()->share(Client::class, function () {
            return new Client(
                $this->getContainer()->get(Connection::class),
                $this->getContainer()->get(Packer::class)
            );
        });

        $this->getContainer()->share(Connection::class, function () {
            return $this->getContainer()->get(StreamConnection::class);
        });

        $this->getContainer()->share(Mapper::class, function () {
            $mapper = new Mapper($this->getContainer()->get(Client::class));

            $annotation = $mapper->addPlugin(Annotation::class);

            $filesystem = $this->getContainer()->get(Filesystem::class);
            foreach ($filesystem->listClasses('Entity') as $class) {
                $annotation->register($class);
            }
            foreach ($filesystem->listClasses('Repository') as $class) {
                $annotation->register($class);
            }


            $mapper->addPlugin(NestedSet::class);
            $mapper->addPlugin(Sequence::class);
            $mapper->addPlugin(Spy::class);

            $mapper->application = $this->getContainer();

            $mapper->addPlugin(new class($mapper) extends Plugin {
                public function afterInstantiate(Entity $entity)
                {
                    $entity->app = $this->mapper->application;
                }
            });

            return $mapper;
        });

        $this->getContainer()->share(Spy::class, function () {
            return $this->getContainer()->get(Mapper::class)->getPlugin(Spy::class);
        });

        $this->getContainer()->share(Packer::class, function () {
            return new PurePacker();
        });

        $this->getContainer()->share(Schema::class, function () {
            return $this->getContainer()->get(Mapper::class)->getSchema();
        });

        $this->getContainer()->share(StreamConnection::class, function () {
            $config = $this->getContainer()->get(Config::class);
            return new StreamConnection($config['tarantool']);
        });

        $this->getContainer()->share(TarantoolClient::class, function () {
            return $this->getContainer()->get(Client::class);
        });
    }
}
