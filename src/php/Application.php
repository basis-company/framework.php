<?php

namespace Basis;

use League\Container\Container;
use League\Container\ReflectionContainer;

class Application
{
    public function __construct($root)
    {
        $this->container = new Container;

        $this->container->share(Container::class, $this->container);
        $this->container->share(Application::class, $this);
        $this->container->share(Filesystem::class, new Filesystem($this, $root));
        $this->container->share(Framework::class, new Framework($this));

        $this->container->addServiceProvider(Providers\Core::class);
        $this->container->addServiceProvider(Providers\Logging::class);
        $this->container->addServiceProvider(Providers\Tarantool::class);

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
}