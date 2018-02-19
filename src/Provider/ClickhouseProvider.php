<?php

namespace Basis\Provider;

use Basis\Config;
use Basis\Service;
use ClickHouseDB\Client;
use League\Container\ServiceProvider\AbstractServiceProvider;

class ClickhouseProvider extends AbstractServiceProvider
{
    protected $provides = [
        Client::class,
    ];

    public function register()
    {
        $this->getContainer()->share(Client::class, function () {
            $serviceName = $this->getContainer()->get(Service::class)->getName();
            $config = $this->getContainer()->get(Config::class)['clickhouse'];

            $clickhouse = new Client($config);
            $clickhouse->write('create database if not exists '.$serviceName);
            $clickhouse->database($serviceName);

            return $clickhouse;
        });
    }
}
