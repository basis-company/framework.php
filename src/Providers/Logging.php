<?php

namespace Basis\Providers;

use Basis\Config;
use Basis\Logger;
use Fluent\Logger\FluentLogger;
use League\Container\ServiceProvider\AbstractServiceProvider;

class Logging extends AbstractServiceProvider
{
    protected $provides = [
        FluentLogger::class,
        Logger::class,
    ];

    public function register()
    {
        $this->container->share(FluentLogger::class, function() {
            $config = $this->container->get(Config::class);
            return new FluentLogger($config['fluent.host'], $config['fluent.port']);
        });

        $this->container->share(Logger::class, function() {
            $fluent = $this->container->get(FluentLogger::class);
            $config = $this->container->get(Config::class);
            return new Logger($fluent, $config['app.name']);
        });

    }
}