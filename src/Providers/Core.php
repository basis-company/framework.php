<?php

namespace Basis\Providers;

use Basis\Config;
use Basis\Converter;
use Basis\Filesystem;
use League\Container\ServiceProvider\AbstractServiceProvider;

class Core extends AbstractServiceProvider
{
    protected $provides = [
        Config::class,
        Converter::class,
    ];

    public function register()
    {
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