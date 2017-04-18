<?php

namespace Basis;

use League\Container\Container;
use League\Container\ReflectionContainer;

class Application
{
    public function __construct($root)
    {
        $fs = new Filesystem($this, $root);

        $this->container = new Container;
        $this->container->share(Application::class, $this);
        $this->container->share(Container::class, $this->container);
        $this->container->share(Framework::class, new Framework($this));
        $this->container->share(Filesystem::class, $fs);
        $this->container->share(Http::class, new Http($this));

        $this->container->addServiceProvider(Providers\Tarantool::class);
        $this->container->addServiceProvider(Providers\Etcd::class);

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
}