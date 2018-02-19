<?php

namespace Basis\Provider;

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
            $config = [
                'host' => $serviceName.'-ch',
                'port' => '8123',
                'username' => 'default',
                'password' => ''
            ];

            $clickhouse = new Client($config);
            $clickhouse->write('create database if not exists '.$serviceName);
            $clickhouse->database($serviceName);

            return $clickhouse;
        });
    }
}
