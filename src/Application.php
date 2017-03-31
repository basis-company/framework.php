<?php

namespace Basis;

use League\Container\Container;
use League\Container\ReflectionContainer;

class Application
{
    private $startTime;

    public function __construct($root)
    {
        $this->startTime = microtime(1);
        $this->container = new Container;

        $fs = new Filesystem($this, $root);

        $this->container->share(Container::class, $this->container);
        $this->container->share(Application::class, $this);
        $this->container->share(Filesystem::class, $fs);
        $this->container->share(Framework::class, new Framework($this));

        $this->container->addServiceProvider(Providers\Core::class);
        $this->container->addServiceProvider(Providers\Service::class);
        $this->container->addServiceProvider(Providers\Tarantool::class);

        foreach($fs->listClasses('Providers') as $provider) {
            $this->container->addServiceProvider($provider);
        }

        $this->container->delegate(new ReflectionContainer());
    }

    public function get($class)
    {
        return $this->container->get($class);
    }

    public function dispatch($command, $params = [])
    {
        return $this->get(Runner::class)->dispatch($command, $params);
    }

    public function getRunningTime()
    {
        return microtime(1) - $this->startTime;
    }
}