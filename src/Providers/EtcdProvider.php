<?php

namespace Basis\Providers;

use Basis\Config;
use Basis\Etcd;
use League\Container\ServiceProvider\AbstractServiceProvider;
use LinkORB\Component\Etcd\Client;

class EtcdProvider extends AbstractServiceProvider
{
    protected $provides = [
        Client::class,
        Etcd::class,
    ];

    public function register()
    {
        $this->getContainer()->share(Client::class, function () {
            $host = getenv('ETCD_SERVICE_HOST') ?: 'etcd';
            $port = getenv('ETCD_SERVICE_PORT') ?: 2379;
            return new Client("http://$host:$port");
        });
        $this->getContainer()->share(Etcd::class, function () {
            $client = $this->getContainer()->get(Client::class);
            $config = $this->getContainer()->get(Config::class);
            return new Etcd($client, $config);
        });
    }
}
