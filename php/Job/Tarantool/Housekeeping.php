<?php

namespace Basis\Job\Tarantool;

use Basis\Container;
use ReflectionProperty;
use SplObjectStorage;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Pool;
use Tarantool\Mapper\Repository;

class Housekeeping
{
    public function run(Container $container)
    {
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
        $persisted = new ReflectionProperty(Repository::class, 'persisted');
        $persisted->setAccessible(true);
        $keys = new ReflectionProperty(Repository::class, 'keys');
        $keys->setAccessible(true);
        $results = new ReflectionProperty(Repository::class, 'results');
        $results->setAccessible(true);

        foreach ($mapper->getRepositories() as $repository) {
            $keys->setValue($repository, new SplObjectStorage());
            $persisted->setValue($repository, []);
            $results->setValue($repository, []);
        }
    }
}
