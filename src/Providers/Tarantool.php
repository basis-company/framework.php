<?php

namespace Basis\Providers;

use Basis\Config;
use Basis\Filesystem;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Tarantool\Client\Client as TarantoolClient;
use Tarantool\Client\Connection\Connection;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Packer\Packer;
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Mapper\Client;
use Tarantool\Mapper\Manager;
use Tarantool\Mapper\Migrations\Migrator;
use Tarantool\Mapper\Schema\Meta;
use Tarantool\Mapper\Schema\Schema;


class Tarantool extends AbstractServiceProvider
{
    protected $provides = [
        Client::class,
        Connection::class,
        Manager::class,
        Migrator::class,
        Meta::class,
        Packer::class,
        Schema::class,
        TarantoolClient::class,
    ];

    public function register()
    {
        $this->getContainer()->share(Client::class, function () {
            return new Client(
                $this->getContainer()->get(Connection::class),
                $this->getContainer()->get(Packer::class)
            );
        });

        $this->getContainer()->share(Connection::class, function () {
            $config = $this->getContainer()->get(Config::class);
            return new StreamConnection('tcp://'.$config['tarantool.host'].':'.$config['tarantool.port']);
        });

        $this->getContainer()->share(Meta::class, function () {
            return $this->getContainer()->get(Manager::class)->getMeta();
        });

        $this->getContainer()->share(Manager::class, function () {
            return new Manager(
                $this->getContainer()->get(Client::class)
            );
        });

        $this->container->share(Migrator::class, function() {
            $migrator = new Migrator();
            $fs = $this->container->get(Filesystem::class);
            foreach($fs->listFiles('resources/migrations') as $path) {
                list($ym, $filename) = explode('/', $path);
                $namespace = date_create_from_format('Ym', $ym)->format('FY');
                $class = $namespace.'\\'.substr($filename, 0, -4);
                if(!class_exists($class, false)) {
                    include $fs->getPath('resources/migrations/'.$path);
                }
                $migrator->registerMigration($class);
            }
            return $migrator;
        });

        $this->getContainer()->share(Packer::class, function () {
            return new PurePacker();
        });

        $this->getContainer()->share(Schema::class, function () {
            return $this->getContainer()->get(Manager::class)->getSchema();
        });

        $this->getContainer()->share(TarantoolClient::class, function() {
            return $this->getContainer()->get(Client::class);
        });

    }
}