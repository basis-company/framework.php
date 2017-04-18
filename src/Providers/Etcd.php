<?php

namespace Basis\Providers;

use League\Container\ServiceProvider\AbstractServiceProvider;
use LinkORB\Component\Etcd\Client;

class Etcd extends AbstractServiceProvider
{
    protected $provides = [
        Client::class,
    ];

    public function register()
    {
        $this->getContainer()->share(Client::class, function () {
            return new Client('http://'.getenv('ETCD_SERVICE_HOST').':'.getenv('ETCD_SERVICE_PORT'));
        });
    }
}