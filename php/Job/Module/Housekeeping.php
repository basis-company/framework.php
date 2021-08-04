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
        if ($container->hasInstance(Lock::class)) {
            $container->get(Lock::class)->releaseLocks();
        }
    }
}
