<?php

namespace Basis;

use League\Container\Container;
use League\Container\ReflectionContainer;

class Application
{
    function __construct($root)
    {
        $this->container = new Container;
        $this->container->share(Application::class, $this);
        $this->container->share(Container::class, $this->container);
        $this->container->delegate(new ReflectionContainer());

        $this->container->share(Filesystem::class, new Filesystem($root));
    }

    function get($class)
    {
        return $this->container->get($class);
    }
}