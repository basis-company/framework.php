<?php

namespace Basis;

use League\Container\Container;
use League\Container\ReflectionContainer;

class Application extends Container
{
    public function __construct($root)
    {
        parent::__construct();

        $fs = new Filesystem($this, $root);

        $this->share(Application::class, $this);
        $this->share(Container::class, $this);
        $this->share(Framework::class, new Framework($this));
        $this->share(Filesystem::class, $fs);
        $this->share(Http::class, new Http($this));

        $this->addServiceProvider(Providers\Tarantool::class);
        $this->addServiceProvider(Providers\Etcd::class);

        foreach($fs->listClasses('Providers') as $provider) {
            $this->addServiceProvider($provider);
        }

        $this->delegate(new ReflectionContainer());
    }

    public function dispatch($command, $params = [])
    {
        return $this->get(Runner::class)->dispatch($command, $params);
    }
}