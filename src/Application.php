<?php

namespace Basis;

use League\Container\Container;
use League\Container\ReflectionContainer;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Plugin\Annotation;
use Tarantool\Mapper\Repository;

class Application extends Container
{
    public function __construct($root)
    {
        parent::__construct();

        $fs = new Filesystem($this, $root);

        $this->share(Application::class, $this);
        $this->share(Container::class, $this);
        $this->share(Filesystem::class, $fs);

        $this->addServiceProvider(Provider\CoreProvider::class);
        $this->addServiceProvider(Provider\ServiceProvider::class);
        $this->addServiceProvider(Provider\TarantoolProvider::class);

        foreach ($fs->listClasses('Provider') as $provider) {
            $this->addServiceProvider($provider);
        }

        $this->delegate(new ReflectionContainer());
    }

    public function dispatch($job, $params = [], $service = null)
    {
        if ($service) {
            if ($this->get(Service::class)->getName() == $service) {
                $service = null;
            }
        }

        $runner = $this->get(Runner::class);
        if (!$service && $runner->hasJob($job)) {
            return $runner->dispatch($job, $params);
        }

        $dispatcher = $this->get(Dispatcher::class);
        return $dispatcher->dispatch($job, $params, $service);
    }

    public function get($alias, array $args = [])
    {
        if (!$this->hasShared($alias, true) && is_subclass_of($alias, Repository::class)) {
            $spaceName = $this->get(Mapper::class)
                ->getPlugin(Annotation::class)
                ->getRepositorySpaceName($alias);

            if ($spaceName) {
                $instance = $this->get(Mapper::class)->getRepository($spaceName);
                $this->share($alias, $instance);
            }
        }
        return parent::get($alias, $args);
    }
}
