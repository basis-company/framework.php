<?php

namespace Basis\Configuration;

use Basis\Container;
use Basis\Toolkit;
use ClickHouseDB\Client;

class Clickhouse
{
    use Toolkit;

    public function init(Container $container)
    {
        $container->share(Client::class, [ $this, 'createClient' ]);
    }

    public function createClient(): Client
    {
        $config = [
            'host' => getenv('CLICKHOUSE_HOST'),
            'port' => getenv('CLICKHOUSE_PORT') ?: '8123',
            'username' => getenv('CLICKHOUSE_USERNAME') ?: 'default',
            'password' => getenv('CLICKHOUSE_PASSWORD') ?: '',
        ];

        $serviceName = $this->app->getName();

        if (!$config['host']) {
            $address = $this->dispatch('resolve.address', [
                'name' => $serviceName . '-ch',
            ]);
            $config['host'] = $address->host;
        }

        $clickhouse = new Client($config);
        $clickhouse->write('create database if not exists ' . $serviceName);
        $clickhouse->database($serviceName);

        return $clickhouse;
    }
}
