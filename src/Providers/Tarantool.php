<?php

namespace Basis\Providers;

use Basis\Config;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Tarantool\Client\Client as TarantoolClient;
use Tarantool\Client\Connection\Connection;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Packer\Packer;
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Mapper\Bootsrap;
use Tarantool\Mapper\Client;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Schema;


class Tarantool extends AbstractServiceProvider
{
    protected $provides = [
        Bootsrap::class,
        Client::class,
        Connection::class,
        Mapper::class,
        Packer::class,
        Schema::class,
        StreamConnection::class,
        TarantoolClient::class,
    ];

    public function register()
    {
        $this->container->share(Bootsrap::class, function() {
            return $this->container->get(Mapper::class)->getBootstrap();
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
            return new Mapper(
                $this->getContainer()->get(Client::class)
            );
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

        $this->getContainer()->share(TarantoolClient::class, function() {
            return $this->getContainer()->get(Client::class);
        });

    }
}