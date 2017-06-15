<?php

namespace Basis\Provider;

use Basis\Config;
use Basis\Converter;
use Basis\Dispatcher;
use Basis\Framework;
use Basis\Filesystem;
use Basis\Http;
use League\Container\ServiceProvider\AbstractServiceProvider;
use LinkORB\Component\Etcd\Client;

class CoreProvider extends AbstractServiceProvider
{
    protected $provides = [
        Config::class,
        Converter::class,
        Dispatcher::class,
        Framework::class,
        Http::class,
    ];

    public function register()
    {
        $this->getContainer()->share(Dispatcher::class, function () {
            return new Dispatcher($this->getContainer()->get(Client::class));
        });

        $this->getContainer()->share(Framework::class, function () {
            return new Framework($this->getContainer());
        });

        $this->getContainer()->share(Http::class, function () {
            return new Http($this->getContainer());
        });

        $this->getContainer()->share(Converter::class, function () {
            return new Converter();
        });

        $this->getContainer()->share(Config::class, function () {
            $fs = $this->getContainer()->get(Filesystem::class);
            $converter = $this->getContainer()->get(Converter::class);
            return new Config($fs, $converter);
        });
    }
}
