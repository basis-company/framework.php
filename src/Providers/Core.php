<?php

namespace Basis\Providers;

use Basis\Config;
use Basis\Converter;
use Basis\Fiber;
use Basis\Filesystem;
use League\Container\ServiceProvider\AbstractServiceProvider;

class Core extends AbstractServiceProvider
{
    protected $provides = [
        Config::class,
        Converter::class,
        Fiber::class,
    ];

    public function register()
    {
        $this->container->share(Fiber::class, function() {
            return new Fiber();
        });

        $this->container->share(Converter::class, function() {
            return new Converter();
        });

        $this->container->share(Config::class, function() {
            $fs = $this->container->get(Filesystem::class);
            $converter = $this->container->get(Converter::class);
            return new Config($fs, $converter);
        });
    }
}