<?php

namespace Basis\Provider;

use Basis\Application;
use Basis\Cache;
use Basis\Config;
use Basis\Converter;
use Basis\Dispatcher;
use Basis\Filesystem;
use Basis\Framework;
use Basis\Service;
use Basis\Http;
use GuzzleHttp\Client;
use League\Container\ServiceProvider\AbstractServiceProvider;

class CoreProvider extends AbstractServiceProvider
{
    protected $provides = [
        Cache::class,
        Config::class,
        Converter::class,
        Dispatcher::class,
        Framework::class,
        Http::class,
    ];

    public function register()
    {
        $this->getContainer()->share(Config::class, function () {
            $app = $this->getContainer()->get(Application::class);
            $framework = $this->getContainer()->get(Framework::class);
            $fs = $this->getContainer()->get(Filesystem::class);
            $converter = $this->getContainer()->get(Converter::class);
            return new Config($app, $framework, $fs, $converter);
        });

        $this->getContainer()->share(Cache::class, function () {
            $converter = $this->getContainer()->get(Converter::class);
            return new Cache($converter);
        });

        $this->getContainer()->share(Converter::class, function () {
            return new Converter();
        });

        $this->getContainer()->share(Dispatcher::class, function () {
            $client = $this->getContainer()->get(Client::class);
            $service = $this->getContainer()->get(Service::class);
            return new Dispatcher($client, $service);
        });

        $this->getContainer()->share(Framework::class, function () {
            return new Framework($this->getContainer(), dirname(dirname(__DIR__)));
        });

        $this->getContainer()->share(Http::class, function () {
            return new Http($this->getContainer()->get(Application::class));
        });
    }
}
