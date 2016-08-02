<?php

namespace Basis;

use League\Container\Container;
use League\Container\ReflectionContainer;

class Application
{
    function __construct($root)
    {
        $container = $this->container = new Container;

        $container->share(Container::class, $container);
        $container->share(Application::class, $this);
        $container->share(Filesystem::class, new Filesystem($root));

        $container->addServiceProvider(Providers\Core::class);
        $container->addServiceProvider(Providers\Logging::class);

        $container->delegate(new ReflectionContainer());
    }

    function get($class)
    {
        return $this->container->get($class);
    }
}