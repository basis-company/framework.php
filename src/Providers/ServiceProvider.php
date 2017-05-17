<?php

namespace Basis\Providers;

use Basis\Config;
use Basis\Event;
use Basis\Service;
use League\Container\ServiceProvider\AbstractServiceProvider;
use LinkORB\Component\Etcd\Client;
use Tarantool\Mapper\Plugins\Spy;

class ServiceProvider extends AbstractServiceProvider
{
    protected $provides = [
        Client::class,
        Event::class,
        Service::class,
    ];

    public function register()
    {
        $this->getContainer()->share(Client::class, function () {
            $host = getenv('ETCD_SERVICE_HOST') ?: 'etcd';
            $port = getenv('ETCD_SERVICE_PORT') ?: 2379;
            return new Client("http://$host:$port");
        });
        $this->getContainer()->share(Event::class, function () {
            $dispatcher = $this->getContainer()->get(Dispatcher::class);
            $config = $this->getContainer()->get(Config::class);
            $spy = $this->getContainer()->get(Spy::class);
            return new Event($dispatcher, $config, $spy);
        });
        $this->getContainer()->share(Service::class, function () {
            $client = $this->getContainer()->get(Client::class);
            $config = $this->getContainer()->get(Config::class);
            return new Service($client, $config);
        });
    }
}
