<?php

namespace Basis\Job\Module;

use Basis\Container;
use Basis\Converter;
use Basis\Lock;
use Basis\Registry;
use ReflectionProperty;
use SplObjectStorage;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Pool;
use Tarantool\Mapper\Repository;

class Housekeeping
{
    public function run(Container $container)
    {
        $registry = $container->get(Registry::class);
        foreach (scandir('php') as $ns) {
            if (in_array($ns, [ '.', '..', 'Entity', 'Metric', 'Procedure', 'Repository' ])) {
                continue;
            }
            foreach ($registry->listClasses($ns) as $class) {
                $container->drop($class);
            }
        }

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
        $container->get(Converter::class)->flushCache();
    }

    public function flush(Mapper $mapper)
    {
        $keys = new ReflectionProperty(Repository::class, 'keys');
        $keys->setAccessible(true);
        $original = new ReflectionProperty(Repository::class, 'original');
        $original->setAccessible(true);
        $persisted = new ReflectionProperty(Repository::class, 'persisted');
        $persisted->setAccessible(true);
        $results = new ReflectionProperty(Repository::class, 'results');
        $results->setAccessible(true);

        foreach ($mapper->getRepositories() as $repository) {
            $keys->setValue($repository, new SplObjectStorage());
            $original->setValue($repository, []);
            $persisted->setValue($repository, []);
            $results->setValue($repository, []);
        }
    }
}
