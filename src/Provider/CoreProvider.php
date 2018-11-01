<?php

namespace Basis\Provider;

use Basis\Application;
use Basis\Cache;
use Basis\Config;
use Basis\Context;
use Basis\Converter;
use Basis\Dispatcher;
use Basis\Filesystem;
use Basis\Framework;
use Basis\Http;
use Basis\Lock;
use Basis\Service;
use GuzzleHttp\Client as GuzzleHttpClient;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Predis\Client as PredisClient;

class CoreProvider extends AbstractServiceProvider
{
    protected $provides = [
        Cache::class,
        Config::class,
        Converter::class,
        Dispatcher::class,
        Framework::class,
        Http::class,
        Lock::class,
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
            $fs = $this->getContainer()->get(Filesystem::class);
            $converter = $this->getContainer()->get(Converter::class);
            return new Cache($fs, $converter);
        });

        $this->getContainer()->share(Lock::class, function () {
            $redis = $this->getContainer()->get(PredisClient::class);
            return new Lock($redis);
        });

        $this->getContainer()->share(Converter::class, function () {
            return new Converter();
        });

        $this->getContainer()->share(Dispatcher::class, function () {
            $context = $this->getContainer()->get(Context::class);
            $client = $this->getContainer()->get(GuzzleHttpClient::class);
            $service = $this->getContainer()->get(Service::class);
            return new Dispatcher($client, $context, $service);
        });

        $this->getContainer()->share(Framework::class, function () {
            return new Framework($this->getContainer(), dirname(dirname(__DIR__)));
        });

        $this->getContainer()->share(Http::class, function () {
            return new Http($this->getContainer()->get(Application::class));
        });

        $this->getContainer()->share(Context::class, function () {
            return new Context($this->getContainer()->get(Application::class));
        });
    }
}
