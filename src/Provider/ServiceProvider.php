<?php

namespace Basis\Provider;

use Basis\Config;
use Basis\Dispatcher;
use Basis\Event;
use Basis\Filesystem;
use Basis\Service;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Tarantool\Mapper\Plugin\Spy;

class ServiceProvider extends AbstractServiceProvider
{
    protected $provides = [
        Event::class,
        Service::class,
    ];

    public function register()
    {
        $this->getContainer()->share(Event::class, function () {
            $dispatcher = $this->getContainer()->get(Dispatcher::class);
            $service = $this->getContainer()->get(Service::class);
            $spy = $this->getContainer()->get(Spy::class);
            $filesystem = $this->getContainer()->get(Filesystem::class);
            return new Event($dispatcher, $service, $spy, $filesystem);
        });

        $this->getContainer()->share(Service::class, function () {
            $config = $this->getContainer()->get(Config::class);
            return new Service($config['service'], $this->getContainer());
        });
    }
}
