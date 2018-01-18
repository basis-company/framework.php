<?php

namespace Basis\Provider;

use Basis\Application;
use Basis\Config;
use Basis\Converter;
use Basis\Dispatcher;
use Basis\Filesystem;
use Basis\Framework;
use Basis\Http;
use League\Container\ServiceProvider\AbstractServiceProvider;

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
        $this->getContainer()->share(Config::class, function () {
            $framework = $this->getContainer()->get(Framework::class);
            $fs = $this->getContainer()->get(Filesystem::class);
            $converter = $this->getContainer()->get(Converter::class);
            return new Config($framework, $fs, $converter);
        });

        $this->getContainer()->share(Converter::class, function () {
            return new Converter();
        });

        $this->getContainer()->share(Dispatcher::class, function () {
            return new Dispatcher();
        });

        $this->getContainer()->share(Framework::class, function () {
            return new Framework($this->getContainer(), dirname(dirname(__DIR__)));
        });

        $this->getContainer()->share(Http::class, function () {
            return new Http($this->getContainer()->get(Application::class));
        });
    }
}
