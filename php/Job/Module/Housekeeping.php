<?php

namespace Basis\Job\Module;

use Basis\Lock;
use Basis\Container;
use ReflectionProperty;
use SplObjectStorage;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Pool;
use Tarantool\Mapper\Repository;

class Housekeeping
{
    public bool $schema = false;

    public function run(Container $container)
    {
        if ($container->hasInstance(Lock::class)) {
            $container->get(Lock::class)->releaseLocks();
        }

        if ($container->hasInstance(Pool::class)) {
            foreach ($container->get(Pool::class)->getMappers() as $mapper) {
                $this->flush($mapper);
            }
        }
        if ($container->hasInstance(Mapper::class)) {
            $this->flush($container->get(Mapper::class));
        }
    }

    private function flush(Mapper $mapper)
    {
        if ($this->schema) {
            $mapper->getSchema()->reset();
            $mapper->getClient()->flushSpaces();
        }
        $keys = new ReflectionProperty(Repository::class, 'keys');
        $keys->setAccessible(true);
        $original = new ReflectionProperty(Repository::class, 'original');
        $original->setAccessible(true);
        $persisted = new ReflectionProperty(Repository::class, 'persisted');
        $persisted->setAccessible(true);
        $results = new ReflectionProperty(Repository::class, 'results');
        $results->setAccessible(true);
        if ($this->schema) {
            $space = new ReflectionProperty(Repository::class, 'space');
            $space->setAccessible(true);
        }

        foreach ($mapper->getRepositories() as $repository) {
            $keys->setValue($repository, new SplObjectStorage());
            $original->setValue($repository, []);
            $persisted->setValue($repository, []);
            $results->setValue($repository, []);
            if ($this->schema) {
                $name = $space->getValue($repository)->getName();
                $space->setValue($repository, $mapper->getSchema()->getSpace($name));
            }
        }
    }
}
