<?php

namespace Basis\Providers;

use Basis\Dispatcher;
use Basis\Event;
use Basis\Framework;
use Basis\Http;
use League\Container\ServiceProvider\AbstractServiceProvider;
use LinkORB\Component\Etcd\Client;

class CoreProvider extends AbstractServiceProvider
{
    protected $provides = [
        Event::class,
        Dispatcher::class,
        Framework::class,
        Http::class,
    ];

    public function register()
    {
        $this->getContainer()->share(Dispatcher::class, function () {
            return new Dispatcher($this->getContainer()->get(Client::class));
        });

        $this->getContainer()->share(Event::class, function () {
            return new Event($this->getContainer()->get(Dispatcher::class));
        });
        $this->getContainer()->share(Framework::class, function () {
            return new Framework($this->getContainer());
        });

        $this->getContainer()->share(Http::class, function () {
            return new Http($this->getContainer());
        });
    }
}
